<?php
namespace TYPO3\CMS\Vidi\GridRenderer;
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
 * Class rendering relation
 */
class Relation implements \TYPO3\CMS\Vidi\GridRenderer\GridRendererInterface {

	/**
	 * Render a representation of the relation on the GUI.
	 *
	 * @param \TYPO3\CMS\Vidi\Domain\Model\Content $content
	 * @param string $propertyName
	 * @param array $configuration
	 * @return string
	 */
	public function render(\TYPO3\CMS\Vidi\Domain\Model\Content $content = NULL, $propertyName = NULL, $configuration = array()) {

		$result = '';

		#var_dump($propertyName);

		if (\TYPO3\CMS\Vidi\Tca\TcaServiceFactory::getFieldService()->hasRelation($propertyName)) {
			$relations = $content[$propertyName];
			if (!empty($relations)) {
				$template = '<li style="list-style: disc">%s</li>';

				// Compute the label of the foreign table.
				$relationDataType = \TYPO3\CMS\Vidi\Tca\TcaServiceFactory::getFieldService()->relationDataType($propertyName);
				$labelField = \TYPO3\CMS\Vidi\Tca\TcaServiceFactory::getTableService($relationDataType)->getLabelField();

				/** @var $relationalContent \TYPO3\CMS\Vidi\Domain\Model\Content */
				foreach ($relations as $relationalContent) {
					$result .= sprintf($template, $relationalContent[$labelField]);
				}
				$result = sprintf('<ul>%s</ul>', $result);
			}
		}
		return $result;
	}
}
?>