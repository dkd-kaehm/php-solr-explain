<?php

namespace ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation;

use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\Nodes\Explain;
use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\Nodes\Leaf;
use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\Nodes\Max;
use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\Nodes\Product;
use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\Nodes\Sum;
use ArrayObject;

/**
 * Parser used to parse the explain content into a node structure.
 */
class Parser
{

	/**
	 * @var ExplainResult
	 */
	protected $explain;

	/**
	 * @param ExplainResult $explain
	 */
	public function injectExplainResult(ExplainResult $explain)
    {
		$this->explain = $explain;
	}

	/**
	 * Invokes the parsing of the raw content and returns the root node.
	 *
	 * @param string
	 * @return  Explain
	 */
	protected function getRootNode($content): Explain
    {
		$tokens = new ArrayObject();
		$this->parseChildNodes($content,$tokens);

		if(isset($tokens[0])) {
			return $tokens[0];
		} else {
			//error in parsing return a new empty dummy node
			return new Explain();
		}
	}

    /**
     * This method is used to parse the node type from the content and retrieve the
     * corresponding node object instance.
     *
     * @param string $tokenName
     * @return Leaf|Max|Product|Sum
     */
	protected function getNodeFromName(string $tokenName): Explain
    {
		if(mb_strpos($tokenName,'sum of:') !== false || mb_strpos($tokenName,'result of:') !== false)  {
			return new Sum();
		}

		if(mb_strpos($tokenName,'product of:') !== false) {
			return new Product();
		}

		if(mb_strpos($tokenName,'max') !== false && mb_strpos($tokenName,'of:')) {
			return new Max();
		}

			//when nothing else matched we have a leaf node
		return new Leaf();
	}

    /**
     * Recursive method to parse the explain content into a child node structure.
     *
     * @param string $contextContent
     * @param ArrayObject $collection
     * @param Explain|null $parent
     * @param int $level
     */
	protected function parseChildNodes(string $contextContent, ArrayObject $collection, Explain $parent = null, int $level = 0)
    {
		$matches = [];

		//look for tokens stating with 0-9* and get all following lines stating with " " space
		preg_match_all('~((?<=^)|(?<=\n))(?<token>[0-9].*?\n)((?=[0-9])|(?=$))~s', $contextContent,$matches);

		if( array_key_exists('token',$matches)) {

			foreach($matches['token'] as $tokenKey => $tokenContent) {
				$nodeParts		= explode(PHP_EOL,$tokenContent);
				$nodeContent	= trim(array_shift($nodeParts));
				$node			= $this->getNodeFromName($nodeContent);
				$score 			= $this->getScoreFromContent($nodeContent);
				$nodeFieldName 	= $this->getFieldNameFromNodeName($nodeContent);

				$node->setContent($nodeContent);
				$node->setParent($parent);
				$node->setScore($score);
				$node->setLevel($level);
				$node->setFieldName($nodeFieldName);
				$collection->append($node);

				$nextLevelContent = $this->removeLeadingSpacesFromNextLevelContent($nodeParts);

				if(trim($nextLevelContent) != '') {
					$level++;
						//walk recursive through the input
					$this->parseChildNodes($nextLevelContent,$node->getChildren(),$node,$level);
				}
			}
		}
	}

	/**
	 * @param $tokenParts
	 * @return string
	 */
	protected function removeLeadingSpacesFromNextLevelContent($tokenParts) {
		$nextLevelContent = '';
		if (count($tokenParts)) {
			$preparedTokens = preg_replace('~^  ~ims', '', $tokenParts);
			$nextLevelContent = implode(PHP_EOL, $preparedTokens);
			return trim($nextLevelContent).PHP_EOL;
		}
		return $nextLevelContent;
	}

	/**
	 * Extracts the score from a token name.
	 *
	 * Input eg: 3.8332133 = idf(docFreq=0, maxDocs=17)
	 * Output eg: 3.8332133
	 *
	 * @param string $nodeName
	 * @return float
	 */
	protected function getScoreFromContent($nodeName) {
		$score = 0.0;
		$scoreMatches 	= [];
		preg_match('~(?<score>[0-9]*\.[^ ]*)~',$nodeName,$scoreMatches);
		if(isset($scoreMatches['score']) && (float) $scoreMatches['score'] > 0) {
			$score = (float) $scoreMatches['score'];
		}

		return $score;
	}

	/**
	 * @param $content
	 * @return string
	 */
	protected function getQueryAttribute($content) {
		$querystring = '';
		$matches = [];
		preg_match("~^#(?<attributes>[^\n]*)~ism", $content, $matches);

		if(isset($matches['attributes'])) {
			$attributes 		= $matches['attributes'];
			$attributeMatches 	= [];
			preg_match('~.*q=(?<querystring>[^&]*)~ism',$attributes,$attributeMatches);
			if(isset($attributeMatches['querystring'])) {
				$querystring = $attributeMatches['querystring'];
					//convert boostvalues without decimals to boost value with
					//decimal eg: foo^20 => foo^20.0
				$querystring = preg_replace('~\^([0-9^.]+)~i','^$1.0',$querystring);
			}
		}

		return $querystring;
	}

	/**
	 * @param $nodeName
	 */
	protected function getFieldNameFromNodeName($nodeName) {
		$result 	= '';
		$matches 	= [];
        if (mb_strpos($nodeName,'weight(Synonym(') !== false ) {
            preg_match('~weight\(Synonym\((?<fieldname>[^\):]*)~', $nodeName, $matches);
        } elseif(mb_strpos($nodeName,'weight(') !== false ){
			preg_match('~weight\((?<fieldname>[^\):]*)~', $nodeName, $matches);
		} elseif (mb_strpos($nodeName, 'queryWeight(') !== false ) {
			preg_match('~queryWeight\((?<fieldname>[^\):]*)~', $nodeName, $matches);
		} elseif (mb_strpos($nodeName,'fieldWeight(') !== false ) {
			preg_match('~fieldWeight\((?<fieldname>[^\):]*)~', $nodeName, $matches);
        }  elseif (mb_strpos($nodeName, 'FunctionQuery(') !== false ) {
			preg_match('~FunctionQuery\([^\(]*\((?<fieldname>[^\):]*)~', $nodeName, $matches);

				//check if it is a nested function query an get inner fieldname
			$lastBracketPos = mb_strpos($matches['fieldname'],'(');
			if($lastBracketPos !== false) {
				$fieldMatch = mb_substr($matches['fieldname'], $lastBracketPos+1, mb_strlen($matches['fieldname']));
				if(!is_numeric($fieldMatch)) {
					$matches['fieldname'] = $fieldMatch;
				}
			}
		}

		if(isset($matches['fieldname'])) {
			$result = $matches['fieldname'];
		}

		return $result;
	}

	/**
	 * Parses the explain content to an explain object wit child nodes.
	 *
	 * @return ExplainResult
	 */
	public function parse(Content $content, MetaData $metaData) {

		$rawContent = $content->getContent();
		$rootNode = $this->getRootNode($rawContent.PHP_EOL);
		$this->explain->setRootNode($rootNode);

		$children = $rootNode->getChildren();
		$this->explain->setChildren($children);

		$this->explain->setDocumentId($metaData->getDocumentId());
		$this->explain->setAttribute(':query',$this->getQueryAttribute($rawContent));

		return $this->explain;
	}
}