<?php

namespace ApacheSolrForTypo3\SolrExplain\Domain\Result\Explanation;

/**
 * Metadata object. Used during parsing and contains some meta data
 * like the corresponding document id or the parsing mode.
 */
class MetaData
{

	/**
	 * @var string
	 */
	protected $documentId = '';

	/**
	 * @var string
	 */
	protected $mode = '';

	/**
	 * @param string $documentId
	 * @param string $mode
	 */
	public function __construct(string $documentId, string $mode)
    {
		$this->setDocumentId($documentId);
		$this->setMode($mode);
	}

	/**
	 * @param string $documentId
	 */
	public function setDocumentId(string $documentId)
    {
		$this->documentId = $documentId;
	}

	/**
	 * @return string
	 */
	public function getDocumentId(): string
    {
		return $this->documentId;
	}

	/**
	 * @param string $mode
	 */
	public function setMode(string $mode)
    {
		$this->mode = $mode;
	}

	/**
	 * @return string
	 */
	public function getMode(): string
    {
		return $this->mode;
	}
}