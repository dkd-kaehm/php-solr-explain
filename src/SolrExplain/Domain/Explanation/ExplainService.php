<?php

namespace SolrExplain\Domain\Explanation;

/**
 * Top level service to build an explain object from the raw response.
 *
 * @author Timo Schmidt <timo.schmidt@aoemedia.de>
 */
class ExplainService {

	/**
	 * @param string $explainContent
	 * @param string $documentId
	 * @param string $mode
	 * @return \SolrExplain\Domain\Explanation\Explain
	 */
	public static function getExplainFromRawContent($explainContent, $documentId, $mode) {
		$content 	= new \SolrExplain\Domain\Explanation\Content($explainContent);
		$metaData 	= new \SolrExplain\Domain\Explanation\MetaData($documentId, $mode);
		$parser 	= new \SolrExplain\Domain\Explanation\Parser();
		$parser->injectExplain(new \SolrExplain\Domain\Explanation\Explain());

		return $parser->parse($content,$metaData);
	}

	/**
	 * @param string $explainContent
	 * @param string $documentId
	 * @param string $mode
	 * @return array
	 */
	public static function getFieldImpactsFromRawContent($explainContent, $documentId, $mode) {
		$explain 	= self::getExplainFromRawContent($explainContent, $documentId, $mode);
		$fieldImpactVisitor = new \SolrExplain\Domain\Explanation\Visitors\SummarizeFieldImpacts();
		$explain->getRootNode()->visitNodes($fieldImpactVisitor);

		return $fieldImpactVisitor->getFieldImpacts();
	}
}