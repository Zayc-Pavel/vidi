<?php
namespace TYPO3\CMS\Vidi\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Fabien Udriot <fabien.udriot@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Repository for accessing Asset
 */
class ContentRepository extends \TYPO3\CMS\Core\Resource\FileRepository {

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseHandle;

	/**
	 * Tell whether it is a raw result (array) or object being returned.
	 *
	 * @var bool
	 */
	protected $rawResult = FALSE;

	/**
	 * The data type to be returned, e.g fe_users, fe_groups, tt_content, etc...
	 * @var string
	 */
	protected $dataType;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * Constructor
	 */
	public function __construct() {

		/** @var \TYPO3\CMS\Vidi\ModuleLoader $moduleLoader */
		$moduleLoader = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Vidi\ModuleLoader');
		$this->dataType = $moduleLoader->getDataType();

		$this->databaseHandle = $GLOBALS['TYPO3_DB'];
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
	}

	/**
	 * Update an content with new information
	 *
	 * @throws \TYPO3\CMS\Vidi\Exception\MissingUidException
	 * @param \TYPO3\CMS\Vidi\Domain\Model\Content|array $content file information
	 * @return void
	 */
	public function updateAsset($content) {
		if (is_object($content)) {
			/** @var $contentObject \TYPO3\CMS\Vidi\Domain\Model\Content */
			$contentObject = $content;
			$content = $contentObject->toArray();
		}

		if (empty($content['uid'])) {
			throw new \TYPO3\CMS\Vidi\Exception\MissingUidException('Missing Uid', 1351605542);
		}

		if (is_array($content['categories'])) {
			$content['categories'] = implode(',', $content['categories']);
		} else {
			unset($content['categories']);
		}

		$data['sys_file'][$content['uid']] = $content;

		/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
		$tce = $this->objectManager->get('TYPO3\CMS\Core\DataHandling\DataHandler');
		$tce->start($data, array());
		$tce->process_datamap();
	}

	/**
	 * Add a new Asset into the repository.
	 *
	 * @param array $content file information
	 * @return int
	 */
	public function addAsset($content = array()) {

		if (empty($content['pid'])) {
			$content['pid'] = '0';
		}
		$key = 'NEW' . rand(100000, 999999);
		$data['sys_file'][$key] = $content;

		/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
		$tce = $this->objectManager->get('TYPO3\CMS\Core\DataHandling\DataHandler');
		#$tce->stripslashes_values = 0; #@todo useful setting?
		$tce->start($data, array());
		$tce->process_datamap();

		return empty($tce->substNEWwithIDs[$key]) ? 0 : $tce->substNEWwithIDs[$key];
	}

	/**
	 * Returns all objects of this repository.
	 *
	 * @return \TYPO3\CMS\Vidi\Domain\Model\Content[]
	 */
	public function findAll() {

		$query = $this->createQuery();
		return $query->setRawResult($this->rawResult)
			->setDataType($this->dataType)
			->execute();
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @param int $uid The identifier of the object to find
	 * @return \TYPO3\CMS\Vidi\Domain\Model\Content The matching object
	 */
	public function findByUid($uid) {
		$matcher = $this->createMatch()->addMatch('uid', $uid);

		$query = $this->createQuery();
		$result = $query->setRawResult($this->rawResult)
			->setDataType($this->dataType)
			->setMatcher($matcher)
			->execute();

		if (is_array($result)) {
			$result = reset($result);
		}
		return $result;
	}

	/**
	 * Finds all Contents given specified matches.
	 *
	 * @param string $propertyName
	 * @param array $values
	 * @return \TYPO3\CMS\Vidi\Domain\Model\Content[]
	 */
	public function findIn($propertyName, array $values) {
		$result = array();
		$find = 'findBy' . ucfirst($propertyName);
		// @todo improve me when implementing $query->matching($query->in('uid', $values))
		foreach ($values as $value) {
			if (strlen($value) > 0) {
				$result[] = $this->$find($value);
			}
		}
		return $result;
	}

	/**
	 * Finds all Contents given specified matches.
	 *
	 * @param \TYPO3\CMS\Vidi\QueryElement\Matcher $matcher
	 * @param \TYPO3\CMS\Vidi\QueryElement\Order $order The order
	 * @param int $limit
	 * @param int $offset
	 * @return \TYPO3\CMS\Vidi\Domain\Model\Content[]
	 */
	public function findBy(\TYPO3\CMS\Vidi\QueryElement\Matcher $matcher, \TYPO3\CMS\Vidi\QueryElement\Order $order = NULL, $limit = NULL, $offset = NULL) {

		$query = $this->createQuery()->setMatcher($matcher);

		if ($order) {
			$query->setOrder($order);
		}

		if ($offset) {
			$query->setOffset($offset);
		}

		if ($limit) {
			$query->setLimit($limit);
		}

		return $query
			->setRawResult($this->rawResult)
			->setDataType($this->dataType)
			->execute();
	}

	/**
	 * Count all Contents given specified matches.
	 *
	 * @param \TYPO3\CMS\Vidi\QueryElement\Matcher $matcher
	 * @return int
	 */
	public function countBy(\TYPO3\CMS\Vidi\QueryElement\Matcher $matcher) {
		$query = $this->createQuery();
		return $query->setMatcher($matcher)->count();
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param \TYPO3\CMS\Vidi\Domain\Model\Content $content The object to remove
	 * @return boolean
	 */
	public function remove($content) {
		$result = FALSE;
		if ($content) {

			/** @var \TYPO3\CMS\Vidi\ModuleLoader $moduleLoader */
			$moduleLoader = $this->objectManager->get('TYPO3\CMS\Vidi\ModuleLoader');
			$deletedField = \TYPO3\CMS\Vidi\Tca\TcaServiceFactory::getTableService()->getDeleteField();

			if ($deletedField) {
				// Mark the record as deleted
				$result = $this->databaseHandle->exec_UPDATEquery($moduleLoader->getDataType(), 'uid = ' . $content->getUid(), array($deletedField => 1));
			} else {
				$result = $this->databaseHandle->exec_DELETEquery($moduleLoader->getDataType(), 'uid = ' . $content->getUid());
			}
		}
		return $result;
	}

	/**
	 * Dispatches magic methods (findBy[Property]())
	 *
	 * @param string $methodName The name of the magic method
	 * @param string $arguments The arguments of the magic method
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException
	 * @return mixed
	 * @api
	 */
	public function __call($methodName, $arguments) {
		if (substr($methodName, 0, 6) === 'findBy' && strlen($methodName) > 7) {
			$result = $this->processMagicCall();
		} elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
			$result = $this->processMagicCall('one');
		} elseif (substr($methodName, 0, 7) === 'countBy' && strlen($methodName) > 8) {
			$result = $this->processMagicCall('count');
		} else {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedMethodException('The method "' . $methodName . '" is not supported by the repository.', 1360838010);
		}
		return $result;
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return \TYPO3\CMS\Vidi\QueryElement\Query
	 * @api
	 */
	public function createQuery() {
		return $this->objectManager->get('TYPO3\CMS\Vidi\QueryElement\Query');
	}

	/**
	 * Returns a matcher object for this repository
	 *
	 * @return \TYPO3\CMS\Vidi\QueryElement\Matcher
	 * @return object
	 */
	public function createMatch() {
		return $this->objectManager->get('TYPO3\CMS\Vidi\QueryElement\Matcher');
	}

	/**
	 * @return boolean
	 */
	public function getRawResult() {
		return $this->rawResult;
	}

	/**
	 * @param boolean $rawResult
	 * @return \TYPO3\CMS\Vidi\Domain\Repository\ContentRepository
	 */
	public function setRawResult($rawResult) {
		$this->rawResult = $rawResult;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDataType() {
		return $this->dataType;
	}

	/**
	 * @param string $dataType
	 * @return \TYPO3\CMS\Vidi\Domain\Repository\ContentRepository
	 */
	public function setDataType($dataType) {
		$this->dataType = $dataType;
		return $this;
	}

	/**
	 * Handle the magic call by properly creating a Query object and returning its result.
	 *
	 * @param string $flag
	 * @return array
	 */
	protected function processMagicCall($flag = '') {

		$query = $this->createQuery();
		$query->setRawResult($this->rawResult)
			->setDataType($this->dataType);

		if ($flag == 'count') {
			$result = $query->count();
		} else {
			$result = $query->execute();
		}

		return $flag == 'one' && !empty($result) ? reset($result) : $result;
	}
}

?>