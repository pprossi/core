<?php
namespace TYPO3\CMS\Core\Core;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Composer\Autoload\ClassMapGenerator;
use TYPO3\CMS\Core\Package\Exception\MissingPackageManifestException;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Generates class loading information (class maps, class aliases etc.) and writes it to files
 * for further inclusion in the bootstrap
 */
class ClassLoadingInformationGenerator {

	/**
	 * @var PackageInterface[]
	 */
	static protected $activeExtensionPackages;

	/**
	 * Returns class loading information for a single package
	 *
	 * @param PackageInterface $package The package to generate the class loading info for
	 * @param bool $useRelativePaths If set to TRUE, make the path relative to the current TYPO3 instance (PATH_site)
	 * @return array
	 */
	public function buildClassLoadingInformationForPackage(PackageInterface $package, $useRelativePaths = FALSE) {
		$classMap = array();
		$psr4 = array();
		$packagePath = $package->getPackagePath();

		try {
			$manifest = self::getPackageManager()->getComposerManifest($package->getPackagePath());
			if (!empty($manifest->autoload->{'psr-4'})) {
				$psr4manifest = json_decode(json_encode($manifest->autoload->{'psr-4'}), TRUE);
				if (is_array($psr4manifest)) {
					foreach ($psr4manifest as $namespacePrefix => $path) {
						$namespacePath = $packagePath . $path;
						if ($useRelativePaths) {
							$psr4[$namespacePrefix] = $this->makePathRelative($namespacePath, realpath($namespacePath));
						} else {
							$psr4[$namespacePrefix] = $namespacePath;
						}
					}
				}
			}
		} catch (MissingPackageManifestException $e) {
			// Ignore missing composer manifest
		}

		foreach (ClassMapGenerator::createMap($packagePath) as $class => $path) {
			if ($this->isIgnoredPath($packagePath, $path)) {
				continue;
			}
			if ($this->isIgnoredClassName($class)) {
				continue;
			}
			if ($useRelativePaths) {
				$classMap[$class] = self::makePathRelative($packagePath, $path);
			} else {
				$classMap[$class] = $path;
			}
		}

		return array('classMap' => $classMap, 'psr-4' => $psr4);
	}

	/**
	 * Check if the class path should be ignored.
	 * Currently only tests folders are ignored.
	 *
	 * @param string $packagePath
	 * @param string $path
	 * @return bool
	 */
	protected function isIgnoredPath($packagePath, $path) {
		if (stripos($this->makePathRelative($packagePath, $path, FALSE), 'tests') !== FALSE) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Check if class name should be ignored.
	 * Currently all classes with suffix "Test" and "Fixture" will be ignored
	 *
	 * @param string $className
	 * @return bool
	 */
	protected function isIgnoredClassName($className) {
		foreach (array('test', 'fixture') as $suffix) {
			if (strtolower(substr($className, 0 - strlen($suffix))) === $suffix) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Returns class alias map for given package
	 *
	 * @param PackageInterface $package The package to generate the class alias info for
	 * @throws \TYPO3\CMS\Core\Error\Exception
	 * @return array
	 */
	public function buildClassAliasMapForPackage(PackageInterface $package) {
		$aliasToClassNameMapping = array();
		$classNameToAliasMapping = array();
		$possibleClassAliasFile = $package->getPackagePath() . 'Migrations/Code/ClassAliasMap.php';
		if (file_exists($possibleClassAliasFile)) {
			$packageAliasMap = require $possibleClassAliasFile;
			if (!is_array($packageAliasMap)) {
				throw new \TYPO3\CMS\Core\Error\Exception('"class alias maps" must return an array', 1422625075);
			}
			foreach ($packageAliasMap as $aliasClassName => $className) {
				$lowerCasedAliasClassName = strtolower($aliasClassName);
				$aliasToClassNameMapping[$lowerCasedAliasClassName] = $className;
				$classNameToAliasMapping[$className][$lowerCasedAliasClassName] = $lowerCasedAliasClassName;
			}
		}

		return array('aliasToClassNameMapping' => $aliasToClassNameMapping, 'classNameToAliasMapping' => $classNameToAliasMapping);
	}

	/**
	 * Generate the class map file
	 * @return string[]
	 * @internal
	 */
	public function buildAutoloadInformationFiles() {
		// Ensure that for each re-build, the packages are fetched again from the package manager
		self::$activeExtensionPackages = NULL;

		$psr4File = $classMapFile = <<<EOF
<?php

// autoload_classmap.php @generated by TYPO3

\$typo3InstallDir = PATH_site;

return array(

EOF;
		$classMap = array();
		$psr4 = array();
		foreach ($this->getActiveExtensionPackages() as $package) {
			$classLoadingInformation = self::buildClassLoadingInformationForPackage($package, TRUE);
			$classMap = array_merge($classMap, $classLoadingInformation['classMap']);
			$psr4 = array_merge($psr4, $classLoadingInformation['psr-4']);
		}

		ksort($classMap);
		ksort($psr4);
		foreach ($classMap as $class => $relativePath) {
			$classMapFile .= sprintf('    %s => %s,', var_export($class, TRUE), self::getPathCode($relativePath)) . LF;
		}
		$classMapFile .= ");\n";

		foreach ($psr4 as $prefix => $relativePath) {
			$psr4File .= sprintf('    %s => array(%s),', var_export($prefix, TRUE), self::getPathCode($relativePath)) . LF;
		}
		$psr4File .= ");\n";

		return array('classMapFile' => $classMapFile, 'psr-4File' => $psr4File);
	}

	/**
	 * Generate a relative path string from an absolute path within a give package path
	 *
	 * @param string $packagePath
	 * @param string $realPathOfClassFile
	 * @param bool $relativeToRoot
	 * @return string
	 */
	protected function makePathRelative($packagePath, $realPathOfClassFile, $relativeToRoot = TRUE) {
		$realPathOfClassFile = GeneralUtility::fixWindowsFilePath($realPathOfClassFile);
		$packageRealPath = GeneralUtility::fixWindowsFilePath(realpath($packagePath));
		$relativePackagePath = rtrim(PathUtility::stripPathSitePrefix($packagePath), '/');
		if ($relativeToRoot) {
			$relativePathToClassFile = $relativePackagePath . '/' . ltrim(substr($realPathOfClassFile, strlen($packageRealPath)), '/');
		} else {
			$relativePathToClassFile = ltrim(substr($realPathOfClassFile, strlen($packageRealPath)), '/');
		}

		return $relativePathToClassFile;
	}

	/**
	 * Generate a relative path string from a relative path
	 *
	 * @param string $relativePathToClassFile
	 * @return string
	 */
	protected function getPathCode($relativePathToClassFile) {
		return '$typo3InstallDir . ' . var_export($relativePathToClassFile, TRUE);
	}

	/**
	 * Build class alias mapping file
	 *
	 * @return string
	 * @throws \Exception
	 * @internal
	 */
	public function buildClassAliasMapFile() {
		$aliasToClassNameMapping = array();
		$classNameToAliasMapping = array();
		foreach (self::getActiveExtensionPackages() as $package) {
			$aliasMappingForPackage = self::buildClassAliasMapForPackage($package);
			$aliasToClassNameMapping = array_merge($aliasToClassNameMapping, $aliasMappingForPackage['aliasToClassNameMapping']);
			$classNameToAliasMapping = array_merge($classNameToAliasMapping, $aliasMappingForPackage['classNameToAliasMapping']);
		}
		$exportArray = array(
			'aliasToClassNameMapping' => $aliasToClassNameMapping,
			'classNameToAliasMapping' => $classNameToAliasMapping
		);
		$fileContent = '<?php' . chr(10) . 'return ';
		$fileContent .= var_export($exportArray, TRUE);
		$fileContent .= ";\n";
		return $fileContent;
	}

	/**
	 * Get all packages except the protected ones, as they are covered already
	 *
	 * @return PackageInterface[]
	 */
	protected function getActiveExtensionPackages() {
		if (self::$activeExtensionPackages === NULL) {
			self::$activeExtensionPackages = array();
			foreach (self::getPackageManager()->getActivePackages() as $package) {
				if (self::isFrameworkPackage($package)) {
					// Skip all core packages as the class loading info is prepared for them already
					continue;
				}
				self::$activeExtensionPackages[] = $package;
			}
		}

		return self::$activeExtensionPackages;
	}

	/**
	 * Check if the package is a framework package (located in typo3/sysext)
	 *
	 * @param PackageInterface $package
	 * @return bool
	 */
	protected function isFrameworkPackage(PackageInterface $package) {
		return $package->getValueFromComposerManifest('type') === 'typo3-cms-framework';
	}

	/**
	 * @return PackageManager
	 * @throws \TYPO3\CMS\Core\Exception
	 */
	protected function getPackageManager() {
		return Bootstrap::getInstance()->getEarlyInstance(PackageManager::class);
	}

}
