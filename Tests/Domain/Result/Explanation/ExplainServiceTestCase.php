<?php

namespace ApacheSolrForTypo3\SolrExplain\Tests\Domain\Result\Explanation;

use ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation\ExplainService;

/**
 * Test for the top level service class.
 */
class ExplainServiceTestCase extends AbstractExplanationTestCase {

	/**
	 * @test
	 */
	public function testFixture1() {
		$content 	= $this->getFixtureContent('3.0.001.txt');
		$result 	= ExplainService::getFieldImpactsFromRawContent(
			$content,
			'foo',
			'bar'
		);

		$this->assertEquals(array('name' => 100), $result);
	}

    /**
 * @test
 */
    public function testCanNotCreateEmptyNodes() {
        $content 	= $this->getFixtureContent('6.2.001.txt');
        $result 	= ExplainService::getFieldImpactsFromRawContent(
            $content,
            'foo',
            'bar'
        );

        $expectedResult = [
            'keywords' => 3.9746099859253836,
            'title' =>  7.1126007378396707,
            'content' => 88.912788999915335
        ];
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     */
    public function testCanParseSynonymeNodes() {
        $content 	= $this->getFixtureContent('6.6.001.txt');
        $result 	= ExplainService::getFieldImpactsFromRawContent(
            $content,
            'foo',
            'bar'
        );

        $expectedResult = [
            'keywords' => 8.2394580648419,
            'description' => 2.1925963619964,
            'tagsH1' => 8.539163146877,
            'content' => 81.028780249768
        ];

        $this->assertEquals($expectedResult, $result);
    }
}