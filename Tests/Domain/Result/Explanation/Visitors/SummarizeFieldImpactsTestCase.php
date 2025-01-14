<?php

namespace ApacheSolrForTypo3\SolrExplain\Tests\Domain\Result\Explanation\Visitors;

use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\Content;
use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\ExplainResult;
use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\MetaData;
use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\Parser;
use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\Visitors\SummarizeFieldImpacts;
use ApacheSolrForTypo3\SolrExplain\Tests\Domain\Result\Explanation\AbstractExplanationTestCase;

class SummarizeFieldImpactsTestCase extends AbstractExplanationTestCase
{

	/**
	 * @return ExplainResult
	 */
	protected function getExplain($filename) {
		$fileContent = $this->getFixtureContent($filename.".txt");
		$content = new Content($fileContent);
		$metaData = new MetaData('P_164345','auto');
		$parser = new Parser();

		$parser->injectExplainResult(new ExplainResult());
		$explain = $parser->parse($content,$metaData);

		return $explain;
	}

	/**
	 * @test
	 */
	public function testCanSummarizeFieldImpactFixture001()
    {
		$explain = $this->getExplain('3.0.001');
		$visitor = new SummarizeFieldImpacts();
		$explain->getRootNode()->visitNodes($visitor);

		$this->assertEquals(100.0,$visitor->getFieldImpact('name'));
		$this->assertEquals(['name'],$visitor->getRelevantFieldNames());
	}

	/**
	 * @test
	 */
	public function testCanSummarizeFieldImpactFixture003()
    {
		$explain = $this->getExplain('3.0.003');
		$visitor = new SummarizeFieldImpacts();
		$explain->getRootNode()->visitNodes($visitor);

		$this->assertEquals(95.75659716876356,$visitor->getFieldImpact('price'));
		$this->assertEquals(['name','manu','price'],$visitor->getRelevantFieldNames());
	}

	/**
	 * @test
	 */
	public function testCanSummarizeFieldImpactFixture004()
    {
		$explain = $this->getExplain('3.0.004');
		$visitor = new SummarizeFieldImpacts();
		$explain->getRootNode()->visitNodes($visitor);

		$this->assertEquals(100.0,$visitor->getFieldImpact('name'));
		$this->assertEquals(['name','price'],$visitor->getRelevantFieldNames());
	}

	/**
	 * @test
	 */
	public function testCanSummarizeCustomTieBreakerFixture()
    {
		$explain = $this->getExplain('custom.tieBreaker');
		$visitor = new SummarizeFieldImpacts();
		$explain->getRootNode()->visitNodes($visitor);

		$this->assertEquals(['expandedcontent','content','doctype'],$visitor->getRelevantFieldNames());
		$this->assertEquals(47.9,round($visitor->getFieldImpact('doctype'),1));
		$this->assertEquals(47.9,round($visitor->getFieldImpact('expandedcontent'),1));
		$this->assertEquals(4.2,round($visitor->getFieldImpact('content'),1));
	}

	/**
	 * @test
	 */
	public function testCanSummarizeCustomTieBreaker2Fixture()
    {
		$explain = $this->getExplain('custom.tieBreaker2');
		$visitor = new SummarizeFieldImpacts();
		$explain->getRootNode()->visitNodes($visitor);

		$this->assertEquals(['pr_title','doctype'],$visitor->getRelevantFieldNames());
		$this->assertEquals(0.07,round($visitor->getFieldImpact('doctype'),2));
		$this->assertEquals(99.93,round($visitor->getFieldImpact('pr_title'),2));
	}


	/**
	 * @test
	 */
	public function testCanSummarizeCustomTieBreaker3Fixture()
    {
		$explain = $this->getExplain('custom.tieBreaker3');
		$visitor = new SummarizeFieldImpacts();
		$explain->getRootNode()->visitNodes($visitor);

		$this->assertEquals(['keywords','expandedcontent','content','description','doctype'],$visitor->getRelevantFieldNames());
		$this->assertEquals(0.00,round($visitor->getFieldImpact('keywords'),2));
		$this->assertEquals(7.55,round($visitor->getFieldImpact('expandedcontent'),2));
		$this->assertEquals(4.72,round($visitor->getFieldImpact('content'),2));
		$this->assertEquals(85.64,round($visitor->getFieldImpact('description'),2));
		$this->assertEquals(2.09, round($visitor->getFieldImpact('doctype'),2));
	}
}
