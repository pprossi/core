<?php

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

namespace TYPO3\CMS\Core\Page;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\CMS\Core\Resource\ResourceCompressor;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * TYPO3 pageRender class
 * This class render the HTML of a webpage, usable for BE and FE
 */
class PageRenderer implements SingletonInterface
{
    // Constants for the part to be rendered
    protected const PART_COMPLETE = 0;
    protected const PART_HEADER = 1;
    protected const PART_FOOTER = 2;

    public const REQUIREJS_SCOPE_CONFIG = 'config';
    public const REQUIREJS_SCOPE_RESOLVE = 'resolve';

    /**
     * @var bool
     */
    protected $compressJavascript = false;

    /**
     * @var bool
     */
    protected $compressCss = false;

    /**
     * @var bool
     */
    protected $removeLineBreaksFromTemplate = false;

    /**
     * @var bool
     */
    protected $concatenateJavascript = false;

    /**
     * @var bool
     */
    protected $concatenateCss = false;

    /**
     * @var bool
     */
    protected $moveJsFromHeaderToFooter = false;

    /**
     * The language key
     * Two character string or 'default'
     *
     * @var string
     */
    protected $lang;

    // Arrays containing associative array for the included files
    /**
     * @var array<string, array>
     */
    protected $jsFiles = [];

    /**
     * @var array
     */
    protected $jsFooterFiles = [];

    /**
     * @var array
     */
    protected $jsLibs = [];

    /**
     * @var array
     */
    protected $jsFooterLibs = [];

    /**
     * @var array<string, array>
     */
    protected $cssFiles = [];

    /**
     * @var array<string, array>
     */
    protected $cssLibs = [];

    /**
     * The title of the page
     *
     * @var string
     */
    protected $title;

    /**
     * Charset for the rendering
     *
     * @var string
     */
    protected $charSet = 'utf-8';

    /**
     * @var string
     */
    protected $favIcon;

    /**
     * @var string
     * @deprecated will be removed in TYPO3 v13.0.
     */
    protected $baseUrl;

    /**
     * @var bool
     * @deprecated will be removed in TYPO3 v13.0. Use DocType instead.
     */
    protected $renderXhtml = true;

    // Static header blocks
    /**
     * @var string
     */
    protected $xmlPrologAndDocType = '';

    /**
     * @var array
     */
    protected $metaTags = [];

    /**
     * @var array
     */
    protected $inlineComments = [];

    /**
     * @var array
     */
    protected $headerData = [];

    /**
     * @var array
     */
    protected $footerData = [];

    /**
     * @var string
     */
    protected $titleTag = '<title>|</title>';

    /**
     * @var string
     * @deprecated will be removed in TYPO3 v13.0
     */
    protected $metaCharsetTag = '<meta http-equiv="Content-Type" content="text/html; charset=|" />';

    /**
     * @var string
     */
    protected $htmlTag = '<html>';

    /**
     * @var string
     */
    protected $headTag = '<head>';

    /**
     * @var string
     * @deprecated will be removed in TYPO3 v13.0.
     */
    protected $baseUrlTag = '<base href="|" />';

    /**
     * @var string
     */
    protected $iconMimeType = '';

    /**
     * @var string
     */
    protected $shortcutTag = '<link rel="icon" href="%1$s"%2$s />';

    // Static inline code blocks
    /**
     * @var array<string, array>
     */
    protected $jsInline = [];

    /**
     * @var array
     */
    protected $jsFooterInline = [];

    /**
     * @var array<string, array>
     */
    protected $cssInline = [];

    /**
     * @var string
     */
    protected $bodyContent;

    /**
     * @var string
     */
    protected $templateFile;

    // Paths to contributed libraries

    /**
     * default path to the requireJS library, relative to the typo3/ directory
     * @var string
     */
    protected $requireJsPath = 'EXT:core/Resources/Public/JavaScript/Contrib/';

    // Internal flags for JS-libraries
    /**
     * if set, the requireJS library is included
     * @var bool
     */
    protected $addRequireJs = false;

    /**
     * Inline configuration for requireJS (internal)
     * @var array
     */
    protected $requireJsConfig = [];

    /**
     * Inline configuration for requireJS from extensions
     *
     * @var array
     */
    protected $additionalRequireJsConfig = [];

    /**
     * Module names of internal requireJS 'paths'
     * @var array
     */
    protected $internalRequireJsPathModuleNames = [];

    /**
     * Inline configuration for requireJS (public)
     * @var array
     */
    protected $publicRequireJsConfig = [];

    /**
     * @var array
     */
    protected $inlineLanguageLabels = [];

    /**
     * @var array
     */
    protected $inlineLanguageLabelFiles = [];

    /**
     * @var array
     */
    protected $inlineSettings = [];

    /**
     * @var array{0: string, 1: string}
     */
    protected $inlineJavascriptWrap = [
        '<script>' . LF . '/*<![CDATA[*/' . LF,
        '/*]]>*/' . LF . '</script>' . LF,
    ];

    /**
     * @var array
     */
    protected $inlineCssWrap = [
        '<style>' . LF . '/*<![CDATA[*/' . LF . '<!-- ' . LF,
        '-->' . LF . '/*]]>*/' . LF . '</style>' . LF,
    ];

    /**
     * Is empty string for HTML and ' /' for XHTML rendering
     *
     * @var string
     */
    protected $endingSlash = '';

    protected JavaScriptRenderer $javaScriptRenderer;
    protected DocType $docType = DocType::html5;

    public function __construct(
        protected readonly FrontendInterface $assetsCache,
        protected readonly MarkerBasedTemplateService $templateService,
        protected readonly MetaTagManagerRegistry $metaTagRegistry,
        protected readonly PackageManager $packageManager,
        protected readonly AssetRenderer $assetRenderer,
        protected readonly ResourceCompressor $resourceCompressor,
        protected readonly RelativeCssPathFixer $relativeCssPathFixer,
        protected readonly LanguageServiceFactory $languageServiceFactory,
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly StreamFactoryInterface $streamFactory,
    ) {
        $this->reset();

        $this->setMetaTag('name', 'generator', 'TYPO3 CMS');
    }

    /**
     * @internal
     */
    public function updateState(array $state): void
    {
        foreach ($state as $var => $value) {
            switch ($var) {
                case 'assetsCache':
                case 'packageManager':
                case 'assetRenderer':
                case 'templateService':
                case 'resourceCompressor':
                case 'relativeCssPathFixer':
                case 'languageServiceFactory':
                case 'responseFactory':
                case 'streamFactory':
                    break;
                case 'metaTagRegistry':
                    $this->metaTagRegistry->updateState($value);
                    break;
                case 'javaScriptRenderer':
                    $this->javaScriptRenderer->updateState($value);
                    break;
                default:
                    $this->{$var} = $value;
                    break;
            }
        }
    }

    /**
     * @internal
     */
    public function getState(): array
    {
        $state = [];
        foreach (get_object_vars($this) as $var => $value) {
            switch ($var) {
                case 'assetsCache':
                case 'packageManager':
                case 'assetRenderer':
                case 'templateService':
                case 'resourceCompressor':
                case 'relativeCssPathFixer':
                case 'languageServiceFactory':
                case 'responseFactory':
                case 'streamFactory':
                    break;
                case 'metaTagRegistry':
                    $state[$var] = $this->metaTagRegistry->getState();
                    break;
                case 'javaScriptRenderer':
                    $state[$var] = $this->javaScriptRenderer->getState();
                    break;
                default:
                    $state[$var] = $value;
                    break;
            }
        }
        return $state;
    }

    public function getJavaScriptRenderer(): JavaScriptRenderer
    {
        return $this->javaScriptRenderer;
    }

    /**
     * Reset all vars to initial values
     */
    protected function reset()
    {
        $this->setDocType(DocType::html5);
        $this->templateFile = 'EXT:core/Resources/Private/Templates/PageRenderer.html';
        $this->jsFiles = [];
        $this->jsFooterFiles = [];
        $this->jsInline = [];
        $this->jsFooterInline = [];
        $this->jsLibs = [];
        $this->cssFiles = [];
        $this->cssInline = [];
        $this->metaTags = [];
        $this->inlineComments = [];
        $this->headerData = [];
        $this->footerData = [];
        $this->javaScriptRenderer = JavaScriptRenderer::create(
            $this->getStreamlinedFileName('EXT:core/Resources/Public/JavaScript/java-script-item-handler.js', true)
        );
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Setters                                   */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Sets the title
     *
     * @param string $title	title of webpage
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Enables/disables rendering of XHTML code
     *
     * @param bool $enable Enable XHTML
     * @deprecated will be removed in TYPO3 v13.0. Use DocType instead.
     */
    public function setRenderXhtml($enable)
    {
        trigger_error('PageRenderer->setRenderXhtml() will be removed in TYPO3 v13.0. Use PageRenderer->setDocType() instead.', E_USER_DEPRECATED);
        $this->renderXhtml = $enable;

        // Whenever XHTML gets disabled, remove the "text/javascript" type from the wrap
        // since this is not needed and may lead to validation errors in the future.
        $this->inlineJavascriptWrap = [
            '<script' . ($enable ? ' type="text/javascript" ' : '') . '>' . LF . '/*<![CDATA[*/' . LF,
            '/*]]>*/' . LF . '</script>' . LF,
        ];
    }

    /**
     * Sets xml prolog and docType
     *
     * @param string $xmlPrologAndDocType Complete tags for xml prolog and docType
     */
    public function setXmlPrologAndDocType($xmlPrologAndDocType)
    {
        $this->xmlPrologAndDocType = $xmlPrologAndDocType;
    }

    /**
     * Sets meta charset
     *
     * @param string $charSet Used charset
     * @deprecated will be removed in TYPO3 v13.0
     */
    public function setCharSet($charSet)
    {
        trigger_error('PageRenderer->setCharSet() will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        $this->charSet = $charSet;
    }

    /**
     * Sets language
     *
     * @param string $lang Used language
     */
    public function setLanguage($lang)
    {
        $this->lang = $lang;
        if ($this->docType === DocType::html5) {
            $languageCode = $lang === 'default' ? 'en' : $lang;
            $this->setHtmlTag('<html lang="' . htmlspecialchars($languageCode) . '">');
        }
    }

    /**
     * Set the meta charset tag
     *
     * @param string $metaCharsetTag
     * @deprecated will be removed in TYPO3 v13.0. Use DocType instead.
     */
    public function setMetaCharsetTag($metaCharsetTag)
    {
        trigger_error('PageRenderer->setMetaCharsetTag() will be removed in TYPO3 v13.0. Use PageRenderer->setDocType() instead.', E_USER_DEPRECATED);
        $this->metaCharsetTag = $metaCharsetTag;
    }

    /**
     * Sets html tag
     *
     * @param string $htmlTag Html tag
     */
    public function setHtmlTag($htmlTag)
    {
        $this->htmlTag = $htmlTag;
    }

    /**
     * Sets HTML head tag
     *
     * @param string $headTag HTML head tag
     */
    public function setHeadTag($headTag)
    {
        $this->headTag = $headTag;
    }

    /**
     * Sets favicon
     *
     * @param string $favIcon
     */
    public function setFavIcon($favIcon)
    {
        $this->favIcon = $favIcon;
    }

    /**
     * Sets icon mime type
     *
     * @param string $iconMimeType
     */
    public function setIconMimeType($iconMimeType)
    {
        $this->iconMimeType = $iconMimeType;
    }

    /**
     * Sets HTML base URL
     *
     * @param string $baseUrl HTML base URL
     * @param bool $isInternalCall only to be used by TYPO3 Core to avoid multiple deprecations.
     * @deprecated will be removed in TYPO3 v13.0 - <base> tags are not supported anymore in TYPO3.
     */
    public function setBaseUrl($baseUrl, bool $isInternalCall = false)
    {
        if (!$isInternalCall) {
            trigger_error('PageRenderer->setBaseUrl() will be removed in TYPO3 v13.0, as <base> tags are not supported by default anymore in TYPO3', E_USER_DEPRECATED);
        }
        $this->baseUrl = $baseUrl;
    }

    /**
     * Sets template file
     *
     * @param string $file
     */
    public function setTemplateFile($file)
    {
        $this->templateFile = $file;
    }

    /**
     * Sets Content for Body
     *
     * @param string $content
     */
    public function setBodyContent($content)
    {
        $this->bodyContent = $content;
    }

    /**
     * Sets path to requireJS library (relative to typo3 directory)
     *
     * @param string $path Path to requireJS library
     */
    public function setRequireJsPath($path)
    {
        $this->requireJsPath = $path;
    }

    public function getRequireJsConfig(string $scope = null): array
    {
        // return basic RequireJS configuration without shim, paths and packages
        if ($scope === static::REQUIREJS_SCOPE_CONFIG) {
            return array_replace_recursive(
                $this->publicRequireJsConfig,
                $this->filterArrayKeys(
                    $this->requireJsConfig,
                    ['shim', 'paths', 'packages'],
                    false
                )
            );
        }
        // return RequireJS configuration for resolving only shim, paths and packages
        if ($scope === static::REQUIREJS_SCOPE_RESOLVE) {
            return $this->filterArrayKeys(
                $this->requireJsConfig,
                ['shim', 'paths', 'packages'],
                true
            );
        }
        return [];
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Enablers / Disablers                      */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Enables MoveJsFromHeaderToFooter
     */
    public function enableMoveJsFromHeaderToFooter()
    {
        $this->moveJsFromHeaderToFooter = true;
    }

    /**
     * Disables MoveJsFromHeaderToFooter
     */
    public function disableMoveJsFromHeaderToFooter()
    {
        $this->moveJsFromHeaderToFooter = false;
    }

    /**
     * Enables compression of javascript
     */
    public function enableCompressJavascript()
    {
        $this->compressJavascript = true;
    }

    /**
     * Disables compression of javascript
     */
    public function disableCompressJavascript()
    {
        $this->compressJavascript = false;
    }

    /**
     * Enables compression of css
     */
    public function enableCompressCss()
    {
        $this->compressCss = true;
    }

    /**
     * Disables compression of css
     */
    public function disableCompressCss()
    {
        $this->compressCss = false;
    }

    /**
     * Enables concatenation of js files
     */
    public function enableConcatenateJavascript()
    {
        $this->concatenateJavascript = true;
    }

    /**
     * Disables concatenation of js files
     */
    public function disableConcatenateJavascript()
    {
        $this->concatenateJavascript = false;
    }

    /**
     * Enables concatenation of css files
     */
    public function enableConcatenateCss()
    {
        $this->concatenateCss = true;
    }

    /**
     * Disables concatenation of css files
     */
    public function disableConcatenateCss()
    {
        $this->concatenateCss = false;
    }

    /**
     * Sets removal of all line breaks in template
     */
    public function enableRemoveLineBreaksFromTemplate()
    {
        $this->removeLineBreaksFromTemplate = true;
    }

    /**
     * Unsets removal of all line breaks in template
     */
    public function disableRemoveLineBreaksFromTemplate()
    {
        $this->removeLineBreaksFromTemplate = false;
    }

    /**
     * Enables Debug Mode
     * This is a shortcut to switch off all compress/concatenate features to enable easier debug
     */
    public function enableDebugMode()
    {
        $this->compressJavascript = false;
        $this->compressCss = false;
        $this->concatenateCss = false;
        $this->concatenateJavascript = false;
        $this->removeLineBreaksFromTemplate = false;
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Getters                                   */
    /*                                                   */
    /*                                                   */
    /*****************************************************/
    /**
     * Gets the title
     *
     * @return string $title Title of webpage
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Gets the charSet
     *
     * @return string $charSet
     * @deprecated will be removed in TYPO3 v13.0. Use DocType instead.
     */
    public function getCharSet()
    {
        trigger_error('PageRenderer->getCharSet() will be removed in TYPO3 v13.0. Use PageRenderer->getDocType() instead.', E_USER_DEPRECATED);
        return $this->charSet;
    }

    /**
     * Gets the language
     *
     * @return string $lang
     */
    public function getLanguage()
    {
        return $this->lang;
    }

    /**
     * Returns rendering mode XHTML or HTML
     *
     * @return bool TRUE if XHTML, FALSE if HTML
     * @deprecated will be removed in TYPO3 v13.0. Use DocType instead.
     */
    public function getRenderXhtml()
    {
        trigger_error('PageRenderer->getRenderXhtml() will be removed in TYPO3 v13.0. Use PageRenderer->getDocType() instead.', E_USER_DEPRECATED);
        return $this->renderXhtml;
    }

    public function setDocType(DocType $docType): void
    {
        $this->docType = $docType;
        $this->renderXhtml = $docType->isXmlCompliant();
        $this->xmlPrologAndDocType = $docType->getDoctypeDeclaration();
        $this->metaCharsetTag = str_replace('utf-8', '|', $docType->getMetaCharsetTag());
        if ($this->getLanguage()) {
            $languageCode = $this->getLanguage() === 'default' ? 'en' : $this->getLanguage();
            $this->setHtmlTag('<html lang="' . htmlspecialchars($languageCode) . '">');
        }

        // Whenever HTML5 is used, remove the "text/javascript" type from the wrap
        // since this is not needed and may lead to validation errors in the future.
        $this->inlineJavascriptWrap = [
            '<script' . ($docType !== DocType::html5 ? ' type="text/javascript" ' : '') . '>' . LF . '/*<![CDATA[*/' . LF,
            '/*]]>*/' . LF . '</script>' . LF,
        ];
    }

    public function getDocType(): DocType
    {
        return $this->docType;
    }

    /**
     * Gets html tag
     *
     * @return string $htmlTag Html tag
     */
    public function getHtmlTag()
    {
        return $this->htmlTag;
    }

    /**
     * Get meta charset
     *
     * @return string
     * @deprecated will be removed in TYPO3 v13.0. Use DocType instead.
     */
    public function getMetaCharsetTag()
    {
        trigger_error('PageRenderer->getMetaCharsetTag() will be removed in TYPO3 v13.0. Use PageRenderer->getDocType() instead.', E_USER_DEPRECATED);
        return $this->metaCharsetTag;
    }

    /**
     * Gets head tag
     *
     * @return string $tag Head tag
     */
    public function getHeadTag()
    {
        return $this->headTag;
    }

    /**
     * Gets favicon
     *
     * @return string $favIcon
     */
    public function getFavIcon()
    {
        return $this->favIcon;
    }

    /**
     * Gets icon mime type
     *
     * @return string $iconMimeType
     */
    public function getIconMimeType()
    {
        return $this->iconMimeType;
    }

    /**
     * Gets HTML base URL
     *
     * @return string $url
     * @deprecated will be removed in TYPO3 v13.0.
     */
    public function getBaseUrl()
    {
        trigger_error('PageRenderer->getBaseUrl() will be removed in TYPO3 v13.0, as <base> tags are not supported by default anymore in TYPO3', E_USER_DEPRECATED);
        return $this->baseUrl;
    }

    /**
     * Gets template file
     *
     * @return string
     */
    public function getTemplateFile()
    {
        return $this->templateFile;
    }

    /**
     * Gets MoveJsFromHeaderToFooter
     *
     * @return bool
     */
    public function getMoveJsFromHeaderToFooter()
    {
        return $this->moveJsFromHeaderToFooter;
    }

    /**
     * Gets compress of javascript
     *
     * @return bool
     */
    public function getCompressJavascript()
    {
        return $this->compressJavascript;
    }

    /**
     * Gets compress of css
     *
     * @return bool
     */
    public function getCompressCss()
    {
        return $this->compressCss;
    }

    /**
     * Gets concatenate of js files
     *
     * @return bool
     */
    public function getConcatenateJavascript()
    {
        return $this->concatenateJavascript;
    }

    /**
     * Gets concatenate of css files
     *
     * @return bool
     */
    public function getConcatenateCss()
    {
        return $this->concatenateCss;
    }

    /**
     * Gets remove of empty lines from template
     *
     * @return bool
     */
    public function getRemoveLineBreaksFromTemplate()
    {
        return $this->removeLineBreaksFromTemplate;
    }

    /**
     * Gets content for body
     *
     * @return string
     */
    public function getBodyContent()
    {
        return $this->bodyContent;
    }

    /**
     * Gets the inline language labels.
     *
     * @return array The inline language labels
     */
    public function getInlineLanguageLabels()
    {
        return $this->inlineLanguageLabels;
    }

    /**
     * Gets the inline language files
     *
     * @return array
     */
    public function getInlineLanguageLabelFiles()
    {
        return $this->inlineLanguageLabelFiles;
    }

    /*****************************************************/
    /*                                                   */
    /*  Public Functions to add Data                     */
    /*                                                   */
    /*                                                   */
    /*****************************************************/

    /**
     * Sets a given meta tag
     *
     * @param string $type The type of the meta tag. Allowed values are property, name or http-equiv
     * @param string $name The name of the property to add
     * @param string $content The content of the meta tag
     * @param array $subProperties Subproperties of the meta tag (like e.g. og:image:width)
     * @param bool $replace Replace earlier set meta tag
     * @throws \InvalidArgumentException
     */
    public function setMetaTag(string $type, string $name, string $content, array $subProperties = [], $replace = true)
    {
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);
        if (!in_array($type, ['property', 'name', 'http-equiv'], true)) {
            throw new \InvalidArgumentException(
                'When setting a meta tag the only types allowed are property, name or http-equiv. "' . $type . '" given.',
                1496402460
            );
        }
        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $manager->addProperty($name, $content, $subProperties, $replace, $type);
    }

    /**
     * Returns the requested meta tag
     */
    public function getMetaTag(string $type, string $name): array
    {
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);

        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $propertyContent = $manager->getProperty($name, $type);

        if (!empty($propertyContent[0])) {
            return [
                'type' => $type,
                'name' => $name,
                'content' => $propertyContent[0]['content'],
            ];
        }
        return [];
    }

    /**
     * Unset the requested meta tag
     */
    public function removeMetaTag(string $type, string $name)
    {
        // Lowercase all the things
        $type = strtolower($type);
        $name = strtolower($name);

        $manager = $this->metaTagRegistry->getManagerForProperty($name);
        $manager->removeProperty($name, $type);
    }

    /**
     * Adds inline HTML comment
     *
     * @param string $comment
     */
    public function addInlineComment($comment)
    {
        if (!in_array($comment, $this->inlineComments)) {
            $this->inlineComments[] = $comment;
        }
    }

    /**
     * Adds header data
     *
     * @param string $data Free header data for HTML header
     */
    public function addHeaderData($data)
    {
        if (!in_array($data, $this->headerData)) {
            $this->headerData[] = $data;
        }
    }

    /**
     * Adds footer data
     *
     * @param string $data Free header data for HTML header
     */
    public function addFooterData($data)
    {
        if (!in_array($data, $this->footerData)) {
            $this->footerData[] = $data;
        }
    }

    /**
     * Adds JS Library. JS Library block is rendered on top of the JS files.
     *
     * @param string $name Arbitrary identifier
     * @param string $file File name
     * @param string|null $type Content Type
     * @param bool $compress Flag if library should be compressed
     * @param bool $forceOnTop Flag if added library should be inserted at begin of this block
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsLibrary($name, $file, $type = '', $compress = false, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = [])
    {
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if (!isset($this->jsLibs[strtolower($name)])) {
            $this->jsLibs[strtolower($name)] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_HEADER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
                'defer' => $defer,
                'crossorigin' => $crossorigin,
                'nomodule' => $nomodule,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds JS Library to Footer. JS Library block is rendered on top of the Footer JS files.
     *
     * @param string $name Arbitrary identifier
     * @param string $file File name
     * @param string|null $type Content Type
     * @param bool $compress Flag if library should be compressed
     * @param bool $forceOnTop Flag if added library should be inserted at begin of this block
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsFooterLibrary($name, $file, $type = '', $compress = false, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = [])
    {
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        $name .= '_jsFooterLibrary';
        if (!isset($this->jsLibs[strtolower($name)])) {
            $this->jsLibs[strtolower($name)] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_FOOTER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
                'defer' => $defer,
                'crossorigin' => $crossorigin,
                'nomodule' => $nomodule,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds JS file
     *
     * @param string $file File name
     * @param string|null $type Content Type
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsFile($file, $type = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = [])
    {
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if (!isset($this->jsFiles[$file])) {
            $this->jsFiles[$file] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_HEADER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
                'defer' => $defer,
                'crossorigin' => $crossorigin,
                'nomodule' => $nomodule,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds JS file to footer
     *
     * @param string $file File name
     * @param string|null $type Content Type
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $async Flag if property 'async="async"' should be added to JavaScript tags
     * @param string $integrity Subresource Integrity (SRI)
     * @param bool $defer Flag if property 'defer="defer"' should be added to JavaScript tags
     * @param string $crossorigin CORS settings attribute
     * @param bool $nomodule Flag if property 'nomodule="nomodule"' should be added to JavaScript tags
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addJsFooterFile($file, $type = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $async = false, $integrity = '', $defer = false, $crossorigin = '', $nomodule = false, array $tagAttributes = [])
    {
        if ($type === null) {
            $type = $this->docType === DocType::html5 ? '' : 'text/javascript';
        }
        if (!isset($this->jsFiles[$file])) {
            $this->jsFiles[$file] = [
                'file' => $file,
                'type' => $type,
                'section' => self::PART_FOOTER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'async' => $async,
                'integrity' => $integrity,
                'defer' => $defer,
                'crossorigin' => $crossorigin,
                'nomodule' => $nomodule,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds JS inline code
     *
     * @param string $name
     * @param string $block
     * @param bool $compress
     * @param bool $forceOnTop
     */
    public function addJsInlineCode($name, $block, $compress = true, $forceOnTop = false)
    {
        if (!isset($this->jsInline[$name]) && !empty($block)) {
            $this->jsInline[$name] = [
                'code' => $block . LF,
                'section' => self::PART_HEADER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
            ];
        }
    }

    /**
     * Adds JS inline code to footer
     *
     * @param string $name
     * @param string $block
     * @param bool $compress
     * @param bool $forceOnTop
     */
    public function addJsFooterInlineCode($name, $block, $compress = true, $forceOnTop = false)
    {
        if (!isset($this->jsInline[$name]) && !empty($block)) {
            $this->jsInline[$name] = [
                'code' => $block . LF,
                'section' => self::PART_FOOTER,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
            ];
        }
    }

    /**
     * Adds CSS file
     *
     * @param string $file
     * @param string $rel
     * @param string $media
     * @param string $title
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $inline
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addCssFile($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $inline = false, array $tagAttributes = [])
    {
        if (!isset($this->cssFiles[$file])) {
            $this->cssFiles[$file] = [
                'file' => $file,
                'rel' => $rel,
                'media' => $media,
                'title' => $title,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'inline' => $inline,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds CSS file
     *
     * @param string $file
     * @param string $rel
     * @param string $media
     * @param string $title
     * @param bool $compress
     * @param bool $forceOnTop
     * @param string $allWrap
     * @param bool $excludeFromConcatenation
     * @param string $splitChar The char used to split the allWrap value, default is "|"
     * @param bool $inline
     * @param array<string, string> $tagAttributes Key => value list of tag attributes
     */
    public function addCssLibrary($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = true, $forceOnTop = false, $allWrap = '', $excludeFromConcatenation = false, $splitChar = '|', $inline = false, array $tagAttributes = [])
    {
        if (!isset($this->cssLibs[$file])) {
            $this->cssLibs[$file] = [
                'file' => $file,
                'rel' => $rel,
                'media' => $media,
                'title' => $title,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
                'allWrap' => $allWrap,
                'excludeFromConcatenation' => $excludeFromConcatenation,
                'splitChar' => $splitChar,
                'inline' => $inline,
                'tagAttributes' => $tagAttributes,
            ];
        }
    }

    /**
     * Adds CSS inline code
     *
     * @param string $name
     * @param string $block
     * @param bool $compress
     * @param bool $forceOnTop
     */
    public function addCssInlineBlock($name, $block, $compress = false, $forceOnTop = false)
    {
        if (!isset($this->cssInline[$name]) && !empty($block)) {
            $this->cssInline[$name] = [
                'code' => $block,
                'compress' => $compress,
                'forceOnTop' => $forceOnTop,
            ];
        }
    }

    /**
     * Call function if you need the requireJS library
     * this automatically adds the JavaScript path of all loaded extensions in the requireJS path option
     * so it resolves names like TYPO3/CMS/MyExtension/MyJsFile to EXT:MyExtension/Resources/Public/JavaScript/MyJsFile.js
     * when using requireJS
     */
    public function loadRequireJs()
    {
        $this->addRequireJs = true;
        $backendUserLoggedIn = !empty($GLOBALS['BE_USER']->user['uid']);
        if ($this->getApplicationType() === 'BE' && $backendUserLoggedIn) {
            // Include all imports in order to be available for prior
            // RequireJS modules migrated to ES6
            $this->javaScriptRenderer->includeAllImports();
        }
        if (!empty($this->requireJsConfig) && !empty($this->publicRequireJsConfig)) {
            return;
        }

        $packages = $this->packageManager->getActivePackages();
        $isDevelopment = Environment::getContext()->isDevelopment();
        $cacheIdentifier = (new PackageDependentCacheIdentifier($this->packageManager))
              ->withPrefix('RequireJS')
              ->withAdditionalHashedIdentifier(($isDevelopment ? ':dev' : '') . GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT'))
              ->toString();
        $requireJsConfig = $this->assetsCache->get($cacheIdentifier);

        // if we did not get a configuration from the cache, compute and store it in the cache
        if (!isset($requireJsConfig['internal']) || !isset($requireJsConfig['public'])) {
            $requireJsConfig = $this->computeRequireJsConfig($isDevelopment, $packages);
            $this->assetsCache->set($cacheIdentifier, $requireJsConfig);
        }

        $this->requireJsConfig = array_merge_recursive($this->additionalRequireJsConfig, $requireJsConfig['internal']);
        $this->additionalRequireJsConfig = [];
        $this->publicRequireJsConfig = $requireJsConfig['public'];
        $this->internalRequireJsPathModuleNames = $requireJsConfig['internalNames'];
    }

    /**
     * Computes the RequireJS configuration, mainly consisting of the paths to the core and all extension JavaScript
     * resource folders plus some additional generic configuration.
     *
     * @param bool $isDevelopment
     * @param array<string, PackageInterface> $packages
     * @return array The RequireJS configuration
     */
    protected function computeRequireJsConfig($isDevelopment, array $packages)
    {
        // load all paths to map to package names / namespaces
        $requireJsConfig = [
            'public' => [],
            'internal' => [],
            'internalNames' => [],
        ];

        $corePath = $packages['core']->getPackagePath() . 'Resources/Public/JavaScript/Contrib/';
        $corePath = PathUtility::getAbsoluteWebPath($corePath);
        // first, load all paths for the namespaces, and configure contrib libs.
        $requireJsConfig['public']['paths'] = [];
        $requireJsConfig['public']['shim'] = [];

        $requireJsConfig['public']['waitSeconds'] = 30;
        $requireJsConfig['public']['typo3BaseUrl'] = false;
        $publicPackageNames = ['core', 'frontend', 'backend'];
        $requireJsExtensionVersions = [];
        foreach ($packages as $packageName => $package) {
            $absoluteJsPath = $package->getPackagePath() . 'Resources/Public/JavaScript/';
            $fullJsPath = PathUtility::getAbsoluteWebPath($absoluteJsPath);
            $fullJsPath = rtrim($fullJsPath, '/');
            if (!empty($fullJsPath) && file_exists($absoluteJsPath)) {
                $type = in_array($packageName, $publicPackageNames, true) ? 'public' : 'internal';
                $requireJsConfig[$type]['paths']['TYPO3/CMS/' . GeneralUtility::underscoredToUpperCamelCase($packageName)] = $fullJsPath;
                $requireJsExtensionVersions[] = $package->getPackageKey() . ':' . $package->getPackageMetadata()->getVersion();
            }
        }
        // sanitize module names in internal 'paths'
        $internalPathModuleNames = array_keys($requireJsConfig['internal']['paths'] ?? []);
        $sanitizedInternalPathModuleNames = array_map(
            static function ($moduleName) {
                // trim spaces and slashes & add ending slash
                return trim($moduleName, ' /') . '/';
            },
            $internalPathModuleNames
        );
        $requireJsConfig['internalNames'] = array_combine(
            $sanitizedInternalPathModuleNames,
            $internalPathModuleNames
        );

        // Add a GET parameter to the files loaded via requireJS in order to avoid browser caching of JS files
        if ($isDevelopment) {
            $requireJsConfig['public']['urlArgs'] = 'bust=' . $GLOBALS['EXEC_TIME'];
        } else {
            $requireJsConfig['public']['urlArgs'] = 'bust=' . GeneralUtility::hmac(
                Environment::getProjectPath() . implode('|', $requireJsExtensionVersions)
            );
        }

        // check if additional AMD modules need to be loaded if a single AMD module is initialized
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RequireJS']['postInitializationModules'] ?? false)) {
            $this->addInlineSettingArray(
                'RequireJS.PostInitializationModules',
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RequireJS']['postInitializationModules']
            );
        }

        return $requireJsConfig;
    }

    /**
     * Add additional configuration to require js.
     *
     * Configuration will be merged recursive with overrule.
     *
     * To add another path mapping deliver the following configuration:
     * 		'paths' => array(
     *			'EXTERN/mybootstrapjs' => 'sysext/.../twbs/bootstrap.min',
     *      ),
     *
     * @param array $configuration The configuration that will be merged with existing one.
     */
    public function addRequireJsConfiguration(array $configuration)
    {
        if ($this->addRequireJs === true) {
            $this->requireJsConfig = array_merge_recursive($this->requireJsConfig, $configuration);
        } else {
            // Delay merge until RequireJS base configuration is loaded
            $this->additionalRequireJsConfig = array_merge_recursive($this->additionalRequireJsConfig, $configuration);
        }
    }

    /**
     * Generates RequireJS loader HTML markup.
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function getRequireJsLoader(): string
    {
        $html = '';
        $backendUserLoggedIn = !empty($GLOBALS['BE_USER']->user['uid']);

        if (!($GLOBALS['TYPO3_REQUEST']) instanceof ServerRequestInterface
            || !ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
        ) {
            // no backend request - basically frontend
            $requireJsConfig = $this->getRequireJsConfig(static::REQUIREJS_SCOPE_CONFIG);
            $requireJsConfig['typo3BaseUrl'] = GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . '?eID=requirejs';
        } elseif (!$backendUserLoggedIn) {
            // backend request, but no backend user logged in
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $requireJsConfig = $this->getRequireJsConfig(static::REQUIREJS_SCOPE_CONFIG);
            $requireJsConfig['typo3BaseUrl'] = (string)$uriBuilder->buildUriFromRoute('ajax_core_requirejs');
        } else {
            // Backend request, having backend user logged in.
            // Merge public and private require js configuration.
            // Use array_merge for 'packages' definitions (scalar array indexes) and
            // merge+replace for other, string array based configuration (like 'path' and 'shim').
            $requireJsConfig = ArrayUtility::replaceAndAppendScalarValuesRecursive(
                $this->publicRequireJsConfig,
                $this->requireJsConfig
            );
        }
        $requireJsUri = $this->processJsFile($this->requireJsPath . 'require.js');
        // add (probably filtered) RequireJS configuration
        if ($this->getApplicationType() === 'BE') {
            $html .= sprintf(
                '<script src="%s"></script>' . "\n",
                htmlspecialchars($requireJsUri)
            );
            $html .= sprintf(
                '<script src="%s">/* %s */</script>' . "\n",
                htmlspecialchars($this->getStreamlinedFileName('EXT:core/Resources/Public/JavaScript/require-jsconfig-handler.js', true)),
                (string)json_encode($requireJsConfig, JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG)
            );
        } else {
            $html .= GeneralUtility::wrapJS('var require = ' . json_encode($requireJsConfig)) . LF;
            // directly after that, include the require.js file
            $html .= sprintf(
                '<script src="%s"></script>' . "\n",
                htmlspecialchars($requireJsUri)
            );
        }
        // use (anonymous require.js loader). Used to shim ES6 modules and when not
        // having a valid TYP3 backend user session.
        if (
            ($this->getApplicationType() === 'BE' && $this->javaScriptRenderer->hasImportMap()) ||
            !empty($requireJsConfig['typo3BaseUrl'])
        ) {
            $html .= '<script src="'
                . $this->getStreamlinedFileName(
                    'EXT:core/Resources/Public/JavaScript/requirejs-loader.js',
                    true
                )
                . '"></script>' . LF;
        }

        return $html;
    }

    /**
     * @param string[] $keys
     */
    protected function filterArrayKeys(array $array, array $keys, bool $keep = true): array
    {
        return array_filter(
            $array,
            static function (string $key) use ($keys, $keep) {
                return in_array($key, $keys, true) === $keep;
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Includes an ES6/ES11 compatible JavaScript module by
     * resolving the specifier to an import-mapped filename.
     *
     * @param string $specifier Bare module identifier like @my/package/Filename.js
     */
    public function loadJavaScriptModule(string $specifier)
    {
        $this->javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create($specifier)
        );
    }

    /**
     * includes an AMD-compatible JS file by resolving the ModuleName, and then requires the file via a requireJS request,
     * additionally allowing to execute JavaScript code afterwards
     *
     * this function only works for AMD-ready JS modules, used like "define('TYPO3/CMS/Backend/FormEngine..."
     * in the JS file
     *
     *	TYPO3/CMS/Backend/FormEngine =>
     * 		"TYPO3": Vendor Name
     * 		"CMS": Product Name
     *		"Backend": Extension Name
     *		"FormEngine": FileName in the Resources/Public/JavaScript folder
     *
     * @param string $mainModuleName Must be in the form of "TYPO3/CMS/PackageName/ModuleName" e.g. "TYPO3/CMS/Backend/FormEngine"
     * @param string $callBackFunction loaded right after the requireJS loading, must be wrapped in function() {}
     * @deprecated will be removed in TYPO3 v13.0. Use loadJavaScriptModule() instead, available since TYPO3 v12.0.
     */
    public function loadRequireJsModule($mainModuleName, $callBackFunction = null, bool $internal = false)
    {
        if (!$internal) {
            trigger_error('PageRenderer->loadRequireJsModule is deprecated in favor of native ES6 modules, use loadJavaScriptModule() instead. Support for RequireJS module loading will be removed in TYPO3 v13.0.', E_USER_DEPRECATED);
        }
        $inlineCodeKey = $mainModuleName;
        // make sure requireJS is initialized
        $this->loadRequireJs();
        // move internal module path definition to public module definition
        // (since loading a module ends up disclosing the existence anyway)
        $baseModuleName = $this->findRequireJsBaseModuleName($mainModuleName);
        if ($baseModuleName !== null && isset($this->requireJsConfig['paths'][$baseModuleName])) {
            $this->publicRequireJsConfig['paths'][$baseModuleName] = $this->requireJsConfig['paths'][$baseModuleName];
            unset($this->requireJsConfig['paths'][$baseModuleName]);
        }
        if ($callBackFunction === null && $this->getApplicationType() === 'BE') {
            $this->javaScriptRenderer->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::forRequireJS($mainModuleName, null, true)
            );
            return;
        }
        // processing frontend application or having callback function
        // @todo deprecate callback function for backend application in TYPO3 v12.0
        if ($callBackFunction === null) {
            // just load the main module
            $inlineCodeKey = $mainModuleName;
            $javaScriptCode = sprintf('require([%s]);', GeneralUtility::quoteJSvalue($mainModuleName));
        } else {
            // load main module and execute possible callback function
            $inlineCodeKey = $mainModuleName . sha1($callBackFunction);
            $javaScriptCode = sprintf('require([%s], %s);', GeneralUtility::quoteJSvalue($mainModuleName), $callBackFunction);
        }
        $this->addJsInlineCode('RequireJS-Module-' . $inlineCodeKey, $javaScriptCode);
    }

    /**
     * Determines requireJS base module name (if defined).
     *
     * @return string|null
     */
    protected function findRequireJsBaseModuleName(string $moduleName)
    {
        // trim spaces and slashes & add ending slash
        $sanitizedModuleName = trim($moduleName, ' /') . '/';
        foreach ($this->internalRequireJsPathModuleNames as $sanitizedBaseModuleName => $baseModuleName) {
            if (str_starts_with($sanitizedModuleName, $sanitizedBaseModuleName)) {
                return $baseModuleName;
            }
        }
        return null;
    }

    /**
     * Adds Javascript Inline Label. This will occur in TYPO3.lang - object
     * The label can be used in scripts with TYPO3.lang.<key>
     *
     * @param string $key
     * @param string $value
     */
    public function addInlineLanguageLabel($key, $value)
    {
        $this->inlineLanguageLabels[$key] = $value;
    }

    /**
     * Adds Javascript Inline Label Array. This will occur in TYPO3.lang - object
     * The label can be used in scripts with TYPO3.lang.<key>
     * Array will be merged with existing array.
     */
    public function addInlineLanguageLabelArray(array $array)
    {
        $this->inlineLanguageLabels = array_merge($this->inlineLanguageLabels, $array);
    }

    /**
     * Gets labels to be used in JavaScript fetched from a locallang file.
     *
     * @param string $fileRef Input is a file-reference (see GeneralUtility::getFileAbsFileName). That file is expected to be a 'locallang.xlf' file containing a valid XML TYPO3 language structure.
     * @param string $selectionPrefix Prefix to select the correct labels (default: '')
     * @param string $stripFromSelectionName String to be removed from the label names in the output. (default: '')
     */
    public function addInlineLanguageLabelFile($fileRef, $selectionPrefix = '', $stripFromSelectionName = '')
    {
        $index = md5($fileRef . $selectionPrefix . $stripFromSelectionName);
        if ($fileRef && !isset($this->inlineLanguageLabelFiles[$index])) {
            $this->inlineLanguageLabelFiles[$index] = [
                'fileRef' => $fileRef,
                'selectionPrefix' => $selectionPrefix,
                'stripFromSelectionName' => $stripFromSelectionName,
            ];
        }
    }

    /**
     * Adds Javascript Inline Setting. This will occur in TYPO3.settings - object
     * The label can be used in scripts with TYPO3.setting.<key>
     *
     * @param string $namespace
     * @param string $key
     * @param mixed $value
     */
    public function addInlineSetting($namespace, $key, $value)
    {
        if ($namespace) {
            if (strpos($namespace, '.')) {
                $parts = explode('.', $namespace);
                $a = &$this->inlineSettings;
                foreach ($parts as $part) {
                    $a = &$a[$part];
                }
                $a[$key] = $value;
            } else {
                $this->inlineSettings[$namespace][$key] = $value;
            }
        } else {
            $this->inlineSettings[$key] = $value;
        }
    }

    /**
     * Adds Javascript Inline Setting. This will occur in TYPO3.settings - object
     * The label can be used in scripts with TYPO3.setting.<key>
     * Array will be merged with existing array.
     *
     * @param string $namespace
     */
    public function addInlineSettingArray($namespace, array $array)
    {
        if ($namespace) {
            if (strpos($namespace, '.')) {
                $parts = explode('.', $namespace);
                $a = &$this->inlineSettings;
                foreach ($parts as $part) {
                    $a = &$a[$part];
                }
                $a = array_merge((array)$a, $array);
            } else {
                $this->inlineSettings[$namespace] = array_merge((array)($this->inlineSettings[$namespace] ?? []), $array);
            }
        } else {
            $this->inlineSettings = array_merge($this->inlineSettings, $array);
        }
    }

    /**
     * Adds content to body content
     *
     * @param string $content
     */
    public function addBodyContent($content)
    {
        $this->bodyContent .= $content;
    }

    /*****************************************************/
    /*                                                   */
    /*  Render Functions                                 */
    /*                                                   */
    /*****************************************************/
    /**
     * Render the page
     *
     * @return string Content of rendered page
     */
    public function render()
    {
        $this->prepareRendering();
        [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs] = $this->renderJavaScriptAndCss();
        $metaTags = implode(LF, array_merge($this->metaTags, $this->renderMetaTagsFromAPI()));
        $markerArray = $this->getPreparedMarkerArray($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs, $metaTags);
        $template = $this->getTemplate();

        // The page renderer needs a full reset when the page was rendered
        $this->reset();
        return trim($this->templateService->substituteMarkerArray($template, $markerArray, '###|###'));
    }

    public function renderResponse(
        int $code = 200,
        string $reasonPhrase = '',
    ): ResponseInterface {
        $stream = $this->streamFactory->createStream($this->render());
        $contentType = 'text/html';
        if ($this->charSet) {
            $contentType .= '; charset=' . $this->charSet;
        }
        return $this->responseFactory->createResponse($code, $reasonPhrase)
            ->withHeader('Content-Type', $contentType)
            ->withBody($stream);
    }

    /**
     * Renders metaTags based on tags added via the API
     *
     * @return array
     */
    protected function renderMetaTagsFromAPI()
    {
        $metaTags = [];
        $metaTagManagers = $this->metaTagRegistry->getAllManagers();

        foreach ($metaTagManagers as $manager => $managerObject) {
            $properties = $managerObject->renderAllProperties();
            if (!empty($properties)) {
                $metaTags[] = $properties;
            }
        }
        return $metaTags;
    }

    /**
     * Render the page but not the JavaScript and CSS Files
     *
     * @param string $substituteHash The hash that is used for the placeholder markers
     * @internal
     * @return string Content of rendered page
     */
    public function renderPageWithUncachedObjects($substituteHash)
    {
        $this->prepareRendering();
        $markerArray = $this->getPreparedMarkerArrayForPageWithUncachedObjects($substituteHash);
        $template = $this->getTemplate();
        return trim($this->templateService->substituteMarkerArray($template, $markerArray, '###|###'));
    }

    /**
     * Renders the JavaScript and CSS files that have been added during processing
     * of uncached content objects (USER_INT, COA_INT)
     *
     * @param string $cachedPageContent
     * @param string $substituteHash The hash that is used for the variables
     * @internal
     * @return string
     */
    public function renderJavaScriptAndCssForProcessingOfUncachedContentObjects($cachedPageContent, $substituteHash)
    {
        $this->prepareRendering();
        [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs] = $this->renderJavaScriptAndCss();
        $title = $this->title ? str_replace('|', htmlspecialchars($this->title), $this->titleTag) : '';
        $markerArray = [
            '<!-- ###TITLE' . $substituteHash . '### -->' => $title,
            '<!-- ###CSS_LIBS' . $substituteHash . '### -->' => $cssLibs,
            '<!-- ###CSS_INCLUDE' . $substituteHash . '### -->' => $cssFiles,
            '<!-- ###CSS_INLINE' . $substituteHash . '### -->' => $cssInline,
            '<!-- ###JS_INLINE' . $substituteHash . '### -->' => $jsInline,
            '<!-- ###JS_INCLUDE' . $substituteHash . '### -->' => $jsFiles,
            '<!-- ###JS_LIBS' . $substituteHash . '### -->' => $jsLibs,
            '<!-- ###META' . $substituteHash . '### -->' => implode(LF, array_merge($this->metaTags, $this->renderMetaTagsFromAPI())),
            '<!-- ###HEADERDATA' . $substituteHash . '### -->' => implode(LF, $this->headerData),
            '<!-- ###FOOTERDATA' . $substituteHash . '### -->' => implode(LF, $this->footerData),
            '<!-- ###JS_LIBS_FOOTER' . $substituteHash . '### -->' => $jsFooterLibs,
            '<!-- ###JS_INCLUDE_FOOTER' . $substituteHash . '### -->' => $jsFooterFiles,
            '<!-- ###JS_INLINE_FOOTER' . $substituteHash . '### -->' => $jsFooterInline,
        ];
        foreach ($markerArray as $placeHolder => $content) {
            $cachedPageContent = str_replace($placeHolder, $content, $cachedPageContent);
        }
        $this->reset();
        return $cachedPageContent;
    }

    /**
     * Remove ending slashes from static header block
     * if the page is being rendered as html (not xhtml)
     * and define property $this->endingSlash for further use
     */
    protected function prepareRendering()
    {
        if ($this->docType->isXmlCompliant()) {
            $this->endingSlash = ' /';
        } else {
            $this->metaCharsetTag = str_replace(' />', '>', $this->metaCharsetTag);
            $this->baseUrlTag = str_replace(' />', '>', $this->baseUrlTag);
            $this->shortcutTag = str_replace(' />', '>', $this->shortcutTag);
            $this->endingSlash = '';
        }
    }

    /**
     * Renders all JavaScript and CSS
     *
     * @return array|string[]
     */
    protected function renderJavaScriptAndCss()
    {
        $this->executePreRenderHook();
        $mainJsLibs = $this->renderMainJavaScriptLibraries();
        if ($this->concatenateJavascript || $this->concatenateCss) {
            // Do the file concatenation
            $this->doConcatenate();
        }
        if ($this->compressCss || $this->compressJavascript) {
            // Do the file compression
            $this->doCompress();
        }
        $this->executeRenderPostTransformHook();
        $cssLibs = $this->renderCssLibraries();
        $cssFiles = $this->renderCssFiles();
        $cssInline = $this->renderCssInline();
        [$jsLibs, $jsFooterLibs] = $this->renderAdditionalJavaScriptLibraries();
        [$jsFiles, $jsFooterFiles] = $this->renderJavaScriptFiles();
        [$jsInline, $jsFooterInline] = $this->renderInlineJavaScript();
        $jsLibs = $mainJsLibs . $jsLibs;
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterLibs = $jsLibs . LF . $jsFooterLibs;
            $jsLibs = '';
            $jsFooterFiles = $jsFiles . LF . $jsFooterFiles;
            $jsFiles = '';
            $jsFooterInline = $jsInline . LF . $jsFooterInline;
            $jsInline = '';
        }
        // Use AssetRenderer to inject all JavaScripts and CSS files
        $jsInline .= $this->assetRenderer->renderInlineJavaScript(true);
        $jsFooterInline .= $this->assetRenderer->renderInlineJavaScript();
        $jsFiles .= $this->assetRenderer->renderJavaScript(true);
        $jsFooterFiles .= $this->assetRenderer->renderJavaScript();
        $cssInline .= $this->assetRenderer->renderInlineStyleSheets(true);
        // append inline CSS to footer (as there is no cssFooterInline)
        $jsFooterFiles .= $this->assetRenderer->renderInlineStyleSheets();
        $cssLibs .= $this->assetRenderer->renderStyleSheets(true, $this->endingSlash);
        $cssFiles .= $this->assetRenderer->renderStyleSheets(false, $this->endingSlash);

        $this->executePostRenderHook($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs);
        return [$jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs];
    }

    /**
     * Fills the marker array with the given strings and trims each value
     *
     * @param string $jsLibs
     * @param string $jsFiles
     * @param string $jsFooterFiles
     * @param string $cssLibs
     * @param string $cssFiles
     * @param string $jsInline
     * @param string $cssInline
     * @param string $jsFooterInline
     * @param string $jsFooterLibs
     * @param string $metaTags
     * @return array Marker array
     */
    protected function getPreparedMarkerArray($jsLibs, $jsFiles, $jsFooterFiles, $cssLibs, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs, $metaTags)
    {
        $markerArray = [
            'XMLPROLOG_DOCTYPE' => $this->xmlPrologAndDocType,
            'HTMLTAG' => $this->htmlTag,
            'HEADTAG' => $this->headTag,
            'METACHARSET' => $this->charSet ? str_replace('|', htmlspecialchars($this->charSet), $this->metaCharsetTag) : '',
            'INLINECOMMENT' => $this->inlineComments ? LF . LF . '<!-- ' . LF . implode(LF, $this->inlineComments) . '-->' . LF . LF : '',
            'BASEURL' => $this->baseUrl ? str_replace('|', $this->baseUrl, $this->baseUrlTag) : '',
            'SHORTCUT' => $this->favIcon ? sprintf($this->shortcutTag, htmlspecialchars($this->favIcon), $this->iconMimeType) : '',
            'CSS_LIBS' => $cssLibs,
            'CSS_INCLUDE' => $cssFiles,
            'CSS_INLINE' => $cssInline,
            'JS_INLINE' => $jsInline,
            'JS_INCLUDE' => $jsFiles,
            'JS_LIBS' => $jsLibs,
            'TITLE' => $this->title ? str_replace('|', htmlspecialchars($this->title), $this->titleTag) : '',
            'META' => $metaTags,
            'HEADERDATA' => $this->headerData ? implode(LF, $this->headerData) : '',
            'FOOTERDATA' => $this->footerData ? implode(LF, $this->footerData) : '',
            'JS_LIBS_FOOTER' => $jsFooterLibs,
            'JS_INCLUDE_FOOTER' => $jsFooterFiles,
            'JS_INLINE_FOOTER' => $jsFooterInline,
            'BODY' => $this->bodyContent,
        ];
        $markerArray = array_map(static fn ($item) => (trim((string)$item)), $markerArray);
        return $markerArray;
    }

    /**
     * Fills the marker array with the given strings and trims each value
     *
     * @param string $substituteHash The hash that is used for the placeholder markers
     * @return array Marker array
     */
    protected function getPreparedMarkerArrayForPageWithUncachedObjects($substituteHash)
    {
        $markerArray = [
            'XMLPROLOG_DOCTYPE' => $this->xmlPrologAndDocType,
            'HTMLTAG' => $this->htmlTag,
            'HEADTAG' => $this->headTag,
            'METACHARSET' => $this->charSet ? str_replace('|', htmlspecialchars($this->charSet), $this->metaCharsetTag) : '',
            'INLINECOMMENT' => $this->inlineComments ? LF . LF . '<!-- ' . LF . implode(LF, $this->inlineComments) . '-->' . LF . LF : '',
            'BASEURL' => $this->baseUrl ? str_replace('|', $this->baseUrl, $this->baseUrlTag) : '',
            'SHORTCUT' => $this->favIcon ? sprintf($this->shortcutTag, htmlspecialchars($this->favIcon), $this->iconMimeType) : '',
            'META' => '<!-- ###META' . $substituteHash . '### -->',
            'BODY' => $this->bodyContent,
            'TITLE' => '<!-- ###TITLE' . $substituteHash . '### -->',
            'CSS_LIBS' => '<!-- ###CSS_LIBS' . $substituteHash . '### -->',
            'CSS_INCLUDE' => '<!-- ###CSS_INCLUDE' . $substituteHash . '### -->',
            'CSS_INLINE' => '<!-- ###CSS_INLINE' . $substituteHash . '### -->',
            'JS_INLINE' => '<!-- ###JS_INLINE' . $substituteHash . '### -->',
            'JS_INCLUDE' => '<!-- ###JS_INCLUDE' . $substituteHash . '### -->',
            'JS_LIBS' => '<!-- ###JS_LIBS' . $substituteHash . '### -->',
            'HEADERDATA' => '<!-- ###HEADERDATA' . $substituteHash . '### -->',
            'FOOTERDATA' => '<!-- ###FOOTERDATA' . $substituteHash . '### -->',
            'JS_LIBS_FOOTER' => '<!-- ###JS_LIBS_FOOTER' . $substituteHash . '### -->',
            'JS_INCLUDE_FOOTER' => '<!-- ###JS_INCLUDE_FOOTER' . $substituteHash . '### -->',
            'JS_INLINE_FOOTER' => '<!-- ###JS_INLINE_FOOTER' . $substituteHash . '### -->',
        ];
        $markerArray = array_map(static fn ($item) => (trim((string)$item)), $markerArray);
        return $markerArray;
    }

    /**
     * Reads the template file and returns the requested part as string
     */
    protected function getTemplate(): string
    {
        $templateFile = GeneralUtility::getFileAbsFileName($this->templateFile);
        if (is_file($templateFile)) {
            $template = (string)file_get_contents($templateFile);
            if ($this->removeLineBreaksFromTemplate) {
                $template = strtr($template, [LF => '', CR => '']);
            }
        } else {
            $template = '';
        }
        return $template;
    }

    /**
     * Helper function for render the main JavaScript libraries,
     * currently: RequireJS
     *
     * @return string Content with JavaScript libraries
     */
    protected function renderMainJavaScriptLibraries()
    {
        $out = '';

        if (!$this->addRequireJs && $this->javaScriptRenderer->hasRequireJs()) {
            $this->loadRequireJs();
        }

        $out .= $this->javaScriptRenderer->renderImportMap(
            // @todo hookup with PSR-7 request/response and
            GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'),
            // @todo add CSP Management API for nonces
            // (currently static for preparatory assertions in Acceptance Testing)
            'rAnd0m'
        );

        // Include RequireJS
        if ($this->addRequireJs) {
            $out .= $this->getRequireJsLoader();
        }

        $this->loadJavaScriptLanguageStrings();
        if ($this->getApplicationType() === 'BE') {
            $noBackendUserLoggedIn = empty($GLOBALS['BE_USER']->user['uid']);
            $this->addAjaxUrlsToInlineSettings($noBackendUserLoggedIn);
        }
        $assignments = array_filter([
            'settings' => $this->inlineSettings,
            'lang' => $this->parseLanguageLabelsForJavaScript(),
        ]);
        if ($assignments !== []) {
            if ($this->getApplicationType() === 'BE') {
                $this->javaScriptRenderer->addGlobalAssignment(['TYPO3' => $assignments]);
            } else {
                $out .= sprintf(
                    "%svar TYPO3 = Object.assign(TYPO3 || {}, %s);\r\n%s",
                    $this->inlineJavascriptWrap[0],
                    // filter potential prototype pollution
                    sprintf(
                        'Object.fromEntries(Object.entries(%s).filter((entry) => '
                            . "!['__proto__', 'prototype', 'constructor'].includes(entry[0])))",
                        json_encode($assignments)
                    ),
                    $this->inlineJavascriptWrap[1],
                );
            }
        }
        $out .= $this->javaScriptRenderer->render();
        return $out;
    }

    /**
     * Converts the language labels for usage in JavaScript
     */
    protected function parseLanguageLabelsForJavaScript(): array
    {
        if (empty($this->inlineLanguageLabels)) {
            return [];
        }

        $labels = [];
        foreach ($this->inlineLanguageLabels as $key => $translationUnit) {
            if (is_array($translationUnit)) {
                $translationUnit = current($translationUnit);
                $labels[$key] = $translationUnit['target'] ?? $translationUnit['source'];
            } else {
                $labels[$key] = $translationUnit;
            }
        }

        return $labels;
    }

    /**
     * Load the language strings into JavaScript
     */
    protected function loadJavaScriptLanguageStrings()
    {
        foreach ($this->inlineLanguageLabelFiles as $languageLabelFile) {
            $this->includeLanguageFileForInline($languageLabelFile['fileRef'], $languageLabelFile['selectionPrefix'], $languageLabelFile['stripFromSelectionName']);
        }
        $this->inlineLanguageLabelFiles = [];
        // Convert settings back to UTF-8 since json_encode() only works with UTF-8:
        if ($this->charSet !== 'utf-8' && is_array($this->inlineSettings)) {
            $this->convertCharsetRecursivelyToUtf8($this->inlineSettings, $this->charSet);
        }
    }

    /**
     * Small helper function to convert charsets for arrays into utf-8
     *
     * @param mixed $data given by reference (string/array usually)
     * @param string $fromCharset convert FROM this charset
     */
    protected function convertCharsetRecursivelyToUtf8(&$data, string $fromCharset)
    {
        foreach ($data as $key => $value) {
            if (is_array($data[$key])) {
                $this->convertCharsetRecursivelyToUtf8($data[$key], $fromCharset);
            } elseif (is_string($data[$key])) {
                $data[$key] = mb_convert_encoding($data[$key], 'utf-8', $fromCharset);
            }
        }
    }

    /**
     * Make URLs to all backend ajax handlers available as inline setting.
     */
    protected function addAjaxUrlsToInlineSettings(bool $publicRoutesOnly = false)
    {
        $ajaxUrls = [];
        // Add the ajax-based routes
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $router = GeneralUtility::makeInstance(Router::class);
        foreach ($router->getRoutes() as $routeIdentifier => $route) {
            if ($publicRoutesOnly && $route->getOption('access') !== 'public') {
                continue;
            }
            if ($route->getOption('ajax')) {
                $uri = (string)$uriBuilder->buildUriFromRoute($routeIdentifier);
                // use the shortened value in order to use this in JavaScript
                $routeIdentifier = str_replace('ajax_', '', $routeIdentifier);
                $ajaxUrls[$routeIdentifier] = $uri;
            }
        }

        $this->inlineSettings['ajaxUrls'] = $ajaxUrls;
    }

    /**
     * Render CSS library files
     *
     * @return string
     */
    protected function renderCssLibraries()
    {
        $cssFiles = '';
        if (!empty($this->cssLibs)) {
            foreach ($this->cssLibs as $file => $properties) {
                $tag = $this->createCssTag($properties, $file);
                if ($properties['forceOnTop'] ?? false) {
                    $cssFiles = $tag . $cssFiles;
                } else {
                    $cssFiles .= $tag;
                }
            }
        }
        return $cssFiles;
    }

    /**
     * Render CSS files
     *
     * @return string
     */
    protected function renderCssFiles()
    {
        $cssFiles = '';
        if (!empty($this->cssFiles)) {
            foreach ($this->cssFiles as $file => $properties) {
                $tag = $this->createCssTag($properties, $file);
                if ($properties['forceOnTop'] ?? false) {
                    $cssFiles = $tag . $cssFiles;
                } else {
                    $cssFiles .= $tag;
                }
            }
        }
        return $cssFiles;
    }

    /**
     * Create link (inline=0) or style (inline=1) tag
     */
    private function createCssTag(array $properties, string $file): string
    {
        $includeInline = $properties['inline'] ?? false;
        $file = $this->getStreamlinedFileName($file, !$includeInline);
        if ($includeInline && @is_file($file)) {
            $tag = $this->createInlineCssTagFromFile($file, $properties);
        } else {
            $tagAttributes = [];
            if ($properties['rel'] ?? false) {
                $tagAttributes['rel'] = $properties['rel'];
            }
            $tagAttributes['href'] = $file;
            if ($properties['media'] ?? false) {
                $tagAttributes['media'] = $properties['media'];
            }
            if ($properties['title'] ?? false) {
                $tagAttributes['title'] = $properties['title'];
            }
            $tagAttributes = array_merge($tagAttributes, $properties['tagAttributes'] ?? []);
            $tag = '<link ' . GeneralUtility::implodeAttributes($tagAttributes, true, true) . $this->endingSlash . '>';
        }
        if ($properties['allWrap'] ?? false) {
            $wrapArr = explode(($properties['splitChar'] ?? false) ?: '|', $properties['allWrap'], 2);
            $tag = $wrapArr[0] . $tag . $wrapArr[1];
        }
        $tag .= LF;

        return $tag;
    }

    /**
     * Render inline CSS
     *
     * @return string
     */
    protected function renderCssInline()
    {
        $cssInline = '';
        if (!empty($this->cssInline)) {
            foreach ($this->cssInline as $name => $properties) {
                $cssCode = '/*' . htmlspecialchars($name) . '*/' . LF . ($properties['code'] ?? '') . LF;
                if ($properties['forceOnTop'] ?? false) {
                    $cssInline = $cssCode . $cssInline;
                } else {
                    $cssInline .= $cssCode;
                }
            }
            $cssInline = $this->inlineCssWrap[0] . $cssInline . $this->inlineCssWrap[1];
        }
        return $cssInline;
    }

    /**
     * Render JavaScript libraries
     *
     * @return array|string[] jsLibs and jsFooterLibs strings
     */
    protected function renderAdditionalJavaScriptLibraries()
    {
        $jsLibs = '';
        $jsFooterLibs = '';
        if (!empty($this->jsLibs)) {
            foreach ($this->jsLibs as $properties) {
                $tagAttributes = [];
                $tagAttributes['src'] = $this->getStreamlinedFileName($properties['file'] ?? '');
                if ($properties['type'] ?? false) {
                    $tagAttributes['type'] = $properties['type'];
                }
                if ($properties['async'] ?? false) {
                    $tagAttributes['async'] = 'async';
                }
                if ($properties['defer'] ?? false) {
                    $tagAttributes['defer'] = 'defer';
                }
                if ($properties['nomodule'] ?? false) {
                    $tagAttributes['nomodule'] = 'nomodule';
                }
                if ($properties['integrity'] ?? false) {
                    $tagAttributes['integrity'] = $properties['integrity'];
                }
                if ($properties['crossorigin'] ?? false) {
                    $tagAttributes['crossorigin'] = $properties['crossorigin'];
                }
                $tagAttributes = array_merge($tagAttributes, $properties['tagAttributes'] ?? []);
                $tag = '<script ' . GeneralUtility::implodeAttributes($tagAttributes, true, true) . '></script>';
                if ($properties['allWrap'] ?? false) {
                    $wrapArr = explode(($properties['splitChar'] ?? false) ?: '|', $properties['allWrap'], 2);
                    $tag = $wrapArr[0] . $tag . $wrapArr[1];
                }
                $tag .= LF;
                if ($properties['forceOnTop'] ?? false) {
                    if (($properties['section'] ?? 0) === self::PART_HEADER) {
                        $jsLibs = $tag . $jsLibs;
                    } else {
                        $jsFooterLibs = $tag . $jsFooterLibs;
                    }
                } elseif (($properties['section'] ?? 0) === self::PART_HEADER) {
                    $jsLibs .= $tag;
                } else {
                    $jsFooterLibs .= $tag;
                }
            }
        }
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterLibs = $jsLibs . LF . $jsFooterLibs;
            $jsLibs = '';
        }
        return [$jsLibs, $jsFooterLibs];
    }

    /**
     * Render JavaScript files
     *
     * @return array|string[] jsFiles and jsFooterFiles strings
     */
    protected function renderJavaScriptFiles()
    {
        $jsFiles = '';
        $jsFooterFiles = '';
        if (!empty($this->jsFiles)) {
            foreach ($this->jsFiles as $file => $properties) {
                $tagAttributes = [];
                $tagAttributes['src'] = $this->getStreamlinedFileName($file);
                if ($properties['type'] ?? false) {
                    $tagAttributes['type'] = $properties['type'];
                }
                if ($properties['async'] ?? false) {
                    $tagAttributes['async'] = 'async';
                }
                if ($properties['defer'] ?? false) {
                    $tagAttributes['defer'] = 'defer';
                }
                if ($properties['nomodule'] ?? false) {
                    $tagAttributes['nomodule'] = 'nomodule';
                }
                if ($properties['integrity'] ?? false) {
                    $tagAttributes['integrity'] = $properties['integrity'];
                }
                if ($properties['crossorigin'] ?? false) {
                    $tagAttributes['crossorigin'] = $properties['crossorigin'];
                }
                $tagAttributes = array_merge($tagAttributes, $properties['tagAttributes'] ?? []);
                $tag = '<script ' . GeneralUtility::implodeAttributes($tagAttributes, true, true) . '></script>';
                if ($properties['allWrap'] ?? false) {
                    $wrapArr = explode(($properties['splitChar'] ?? false) ?: '|', $properties['allWrap'], 2);
                    $tag = $wrapArr[0] . $tag . $wrapArr[1];
                }
                $tag .= LF;
                if ($properties['forceOnTop'] ?? false) {
                    if (($properties['section'] ?? 0) === self::PART_HEADER) {
                        $jsFiles = $tag . $jsFiles;
                    } else {
                        $jsFooterFiles = $tag . $jsFooterFiles;
                    }
                } elseif (($properties['section'] ?? 0) === self::PART_HEADER) {
                    $jsFiles .= $tag;
                } else {
                    $jsFooterFiles .= $tag;
                }
            }
        }
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterFiles = $jsFiles . $jsFooterFiles;
            $jsFiles = '';
        }
        return [$jsFiles, $jsFooterFiles];
    }

    /**
     * Render inline JavaScript
     *
     * @return array|string[] jsInline and jsFooterInline string
     */
    protected function renderInlineJavaScript()
    {
        $jsInline = '';
        $jsFooterInline = '';
        if (!empty($this->jsInline)) {
            foreach ($this->jsInline as $name => $properties) {
                $jsCode = '/*' . htmlspecialchars($name) . '*/' . LF . ($properties['code'] ?? '') . LF;
                if ($properties['forceOnTop'] ?? false) {
                    if (($properties['section'] ?? 0) === self::PART_HEADER) {
                        $jsInline = $jsCode . $jsInline;
                    } else {
                        $jsFooterInline = $jsCode . $jsFooterInline;
                    }
                } elseif (($properties['section'] ?? 0) === self::PART_HEADER) {
                    $jsInline .= $jsCode;
                } else {
                    $jsFooterInline .= $jsCode;
                }
            }
        }
        if ($jsInline) {
            $jsInline = $this->inlineJavascriptWrap[0] . $jsInline . $this->inlineJavascriptWrap[1];
        }
        if ($jsFooterInline) {
            $jsFooterInline = $this->inlineJavascriptWrap[0] . $jsFooterInline . $this->inlineJavascriptWrap[1];
        }
        if ($this->moveJsFromHeaderToFooter) {
            $jsFooterInline = $jsInline . $jsFooterInline;
            $jsInline = '';
        }
        return [$jsInline, $jsFooterInline];
    }

    /**
     * Include language file for inline usage
     *
     * @param string $fileRef
     * @param string $selectionPrefix
     * @param string $stripFromSelectionName
     */
    protected function includeLanguageFileForInline($fileRef, $selectionPrefix = '', $stripFromSelectionName = '')
    {
        $labelsFromFile = [];
        $allLabels = $this->readLLfile($fileRef);

        // Iterate through all labels from the language file
        foreach ($allLabels as $label => $value) {
            // If $selectionPrefix is set, only respect labels that start with $selectionPrefix
            if ($selectionPrefix === '' || str_starts_with($label, $selectionPrefix)) {
                // Remove substring $stripFromSelectionName from label
                $label = str_replace($stripFromSelectionName, '', $label);
                $labelsFromFile[$label] = $value;
            }
        }
        $this->inlineLanguageLabels = array_merge($this->inlineLanguageLabels, $labelsFromFile);
    }

    /**
     * Reads a locallang file.
     *
     * @param string $fileRef Reference to a relative filename to include.
     * @return array Returns the $LOCAL_LANG array found in the file. If no array found, returns empty array.
     */
    protected function readLLfile(string $fileRef): array
    {
        $languageService = $this->languageServiceFactory->create($this->lang);
        return $languageService->getLabelsFromResource($fileRef);
    }

    /*****************************************************/
    /*                                                   */
    /*  Tools                                            */
    /*                                                   */
    /*****************************************************/
    /**
     * Concatenate files into one file
     * registered handler
     */
    protected function doConcatenate()
    {
        $this->doConcatenateCss();
        $this->doConcatenateJavaScript();
    }

    /**
     * Concatenate JavaScript files according to the configuration. Only possible in TYPO3 Frontend.
     */
    protected function doConcatenateJavaScript()
    {
        if ($this->getApplicationType() !== 'FE') {
            return;
        }
        if (!$this->concatenateJavascript) {
            return;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['jsConcatenateHandler'])) {
            // use external concatenation routine
            $params = [
                'jsLibs' => &$this->jsLibs,
                'jsFiles' => &$this->jsFiles,
                'jsFooterFiles' => &$this->jsFooterFiles,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
            ];
            GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['FE']['jsConcatenateHandler'], $params, $this);
        } else {
            $this->jsLibs = $this->resourceCompressor->concatenateJsFiles($this->jsLibs);
            $this->jsFiles = $this->resourceCompressor->concatenateJsFiles($this->jsFiles);
            $this->jsFooterFiles = $this->resourceCompressor->concatenateJsFiles($this->jsFooterFiles);
        }
    }

    /**
     * Concatenate CSS files according to configuration. Only possible in TYPO3 Frontend.
     */
    protected function doConcatenateCss()
    {
        if ($this->getApplicationType() !== 'FE') {
            return;
        }
        if (!$this->concatenateCss) {
            return;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['cssConcatenateHandler'])) {
            // use external concatenation routine
            $params = [
                'cssFiles' => &$this->cssFiles,
                'cssLibs' => &$this->cssLibs,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
            ];
            GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['FE']['cssConcatenateHandler'], $params, $this);
        } else {
            $this->cssLibs = $this->resourceCompressor->concatenateCssFiles($this->cssLibs);
            $this->cssFiles = $this->resourceCompressor->concatenateCssFiles($this->cssFiles);
        }
    }

    /**
     * Compresses inline code
     */
    protected function doCompress()
    {
        $this->doCompressJavaScript();
        $this->doCompressCss();
    }

    /**
     * Compresses CSS according to configuration. Only possible in TYPO3 Frontend.
     */
    protected function doCompressCss()
    {
        if ($this->getApplicationType() !== 'FE') {
            return;
        }
        if (!$this->compressCss) {
            return;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['cssCompressHandler'])) {
            // Use external compression routine
            $params = [
                'cssInline' => &$this->cssInline,
                'cssFiles' => &$this->cssFiles,
                'cssLibs' => &$this->cssLibs,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
            ];
            GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['FE']['cssCompressHandler'], $params, $this);
        } else {
            $this->cssLibs = $this->resourceCompressor->compressCssFiles($this->cssLibs);
            $this->cssFiles = $this->resourceCompressor->compressCssFiles($this->cssFiles);
        }
    }

    /**
     * Compresses JavaScript according to configuration. Only possible in TYPO3 Frontend.
     */
    protected function doCompressJavaScript()
    {
        if ($this->getApplicationType() !== 'FE') {
            return;
        }
        if (!$this->compressJavascript) {
            return;
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'])) {
            // Use external compression routine
            $params = [
                'jsInline' => &$this->jsInline,
                'jsFooterInline' => &$this->jsFooterInline,
                'jsLibs' => &$this->jsLibs,
                'jsFiles' => &$this->jsFiles,
                'jsFooterFiles' => &$this->jsFooterFiles,
                'headerData' => &$this->headerData,
                'footerData' => &$this->footerData,
            ];
            GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler'], $params, $this);
        } else {
            // Traverse the arrays, compress files
            foreach ($this->jsInline ?? [] as $name => $properties) {
                if ($properties['compress'] ?? false) {
                    $this->jsInline[$name]['code'] = $this->resourceCompressor->compressJavaScriptSource($properties['code'] ?? '');
                }
            }
            $this->jsLibs = $this->resourceCompressor->compressJsFiles($this->jsLibs);
            $this->jsFiles = $this->resourceCompressor->compressJsFiles($this->jsFiles);
            $this->jsFooterFiles = $this->resourceCompressor->compressJsFiles($this->jsFooterFiles);
        }
    }

    /**
     * Processes a Javascript file dependent on the current context
     *
     * Adds the version number for Frontend, compresses the file for Backend
     *
     * @param string $filename Filename
     * @return string New filename
     */
    protected function processJsFile($filename)
    {
        $filename = $this->getStreamlinedFileName($filename, false);
        if ($this->getApplicationType() === 'FE') {
            if ($this->compressJavascript) {
                $filename = $this->resourceCompressor->compressJsFile($filename);
            } else {
                $filename = GeneralUtility::createVersionNumberedFilename($filename);
            }
        }
        return $this->getAbsoluteWebPath($filename);
    }

    /**
     * This function acts as a wrapper to allow relative and paths starting with EXT: to be dealt with
     * in this very case to always return the absolute web path to be included directly before output.
     *
     * This is mainly added so the EXT: syntax can be resolved for PageRenderer in one central place,
     * and hopefully removed in the future by one standard API call.
     *
     * @param string $file the filename to process
     * @param bool $prepareForOutput whether the file should be prepared as version numbered file and prefixed as absolute webpath
     * @return string
     * @internal
     */
    protected function getStreamlinedFileName($file, $prepareForOutput = true)
    {
        if (PathUtility::isExtensionPath($file)) {
            $file = Environment::getPublicPath() . '/' . PathUtility::getPublicResourceWebPath($file, false);
            // as the path is now absolute, make it "relative" to the current script to stay compatible
            $file = PathUtility::getRelativePathTo($file) ?? '';
            $file = rtrim($file, '/');
        } else {
            $file = GeneralUtility::resolveBackPath($file);
        }
        if ($prepareForOutput) {
            $file = GeneralUtility::createVersionNumberedFilename($file);
            $file = $this->getAbsoluteWebPath($file);
        }
        return $file;
    }

    /**
     * Gets absolute web path of filename for backend disposal.
     * Resolving the absolute path in the frontend with conflict with
     * applying config.absRefPrefix in frontend rendering process.
     *
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::setAbsRefPrefix()
     */
    protected function getAbsoluteWebPath(string $file): string
    {
        if ($this->getApplicationType() === 'FE') {
            return $file;
        }
        return PathUtility::getAbsoluteWebPath($file);
    }

    /*****************************************************/
    /*                                                   */
    /*  Hooks                                            */
    /*                                                   */
    /*****************************************************/
    /**
     * Execute PreRenderHook for possible manipulation
     */
    protected function executePreRenderHook()
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'] ?? false;
        if (!$hooks) {
            return;
        }
        $params = [
            'jsLibs' => &$this->jsLibs,
            'jsFooterLibs' => &$this->jsFooterLibs,
            'jsFiles' => &$this->jsFiles,
            'jsFooterFiles' => &$this->jsFooterFiles,
            'cssLibs' => &$this->cssLibs,
            'cssFiles' => &$this->cssFiles,
            'headerData' => &$this->headerData,
            'footerData' => &$this->footerData,
            'jsInline' => &$this->jsInline,
            'jsFooterInline' => &$this->jsFooterInline,
            'cssInline' => &$this->cssInline,
        ];
        foreach ($hooks as $hook) {
            GeneralUtility::callUserFunction($hook, $params, $this);
        }
    }

    /**
     * PostTransform for possible manipulation of concatenated and compressed files
     */
    protected function executeRenderPostTransformHook()
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform'] ?? false;
        if (!$hooks) {
            return;
        }
        $params = [
            'jsLibs' => &$this->jsLibs,
            'jsFooterLibs' => &$this->jsFooterLibs,
            'jsFiles' => &$this->jsFiles,
            'jsFooterFiles' => &$this->jsFooterFiles,
            'cssLibs' => &$this->cssLibs,
            'cssFiles' => &$this->cssFiles,
            'headerData' => &$this->headerData,
            'footerData' => &$this->footerData,
            'jsInline' => &$this->jsInline,
            'jsFooterInline' => &$this->jsFooterInline,
            'cssInline' => &$this->cssInline,
        ];
        foreach ($hooks as $hook) {
            GeneralUtility::callUserFunction($hook, $params, $this);
        }
    }

    /**
     * Execute postRenderHook for possible manipulation
     *
     * @param string $jsLibs
     * @param string $jsFiles
     * @param string $jsFooterFiles
     * @param string $cssLibs
     * @param string $cssFiles
     * @param string $jsInline
     * @param string $cssInline
     * @param string $jsFooterInline
     * @param string $jsFooterLibs
     */
    protected function executePostRenderHook(&$jsLibs, &$jsFiles, &$jsFooterFiles, &$cssLibs, &$cssFiles, &$jsInline, &$cssInline, &$jsFooterInline, &$jsFooterLibs)
    {
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'] ?? false;
        if (!$hooks) {
            return;
        }
        $params = [
            'jsLibs' => &$jsLibs,
            'jsFiles' => &$jsFiles,
            'jsFooterFiles' => &$jsFooterFiles,
            'cssLibs' => &$cssLibs,
            'cssFiles' => &$cssFiles,
            'headerData' => &$this->headerData,
            'footerData' => &$this->footerData,
            'jsInline' => &$jsInline,
            'cssInline' => &$cssInline,
            'xmlPrologAndDocType' => &$this->xmlPrologAndDocType,
            'htmlTag' => &$this->htmlTag,
            'headTag' => &$this->headTag,
            'charSet' => &$this->charSet,
            'metaCharsetTag' => &$this->metaCharsetTag,
            'shortcutTag' => &$this->shortcutTag,
            'inlineComments' => &$this->inlineComments,
            'baseUrl' => &$this->baseUrl,
            'baseUrlTag' => &$this->baseUrlTag,
            'favIcon' => &$this->favIcon,
            'iconMimeType' => &$this->iconMimeType,
            'titleTag' => &$this->titleTag,
            'title' => &$this->title,
            'metaTags' => &$this->metaTags,
            'jsFooterInline' => &$jsFooterInline,
            'jsFooterLibs' => &$jsFooterLibs,
            'bodyContent' => &$this->bodyContent,
        ];
        foreach ($hooks as $hook) {
            GeneralUtility::callUserFunction($hook, $params, $this);
        }
    }

    /**
     * Creates a CSS inline tag
     *
     * @param string $file the filename to process
     */
    protected function createInlineCssTagFromFile(string $file, array $properties): string
    {
        $cssInline = file_get_contents($file);
        if ($cssInline === false) {
            return '';
        }
        $cssInlineFix = $this->relativeCssPathFixer->fixRelativeUrlPaths($cssInline, '/' . PathUtility::dirname($file) . '/');
        $tagAttributes = [];
        if ($properties['media'] ?? false) {
            $tagAttributes['media'] = $properties['media'];
        }
        if ($properties['title'] ?? false) {
            $tagAttributes['title'] = $properties['title'];
        }
        $tagAttributes = array_merge($tagAttributes, $properties['tagAttributes'] ?? []);
        return '<style ' . GeneralUtility::implodeAttributes($tagAttributes, true, true) . '>' . LF
            . '/*<![CDATA[*/' . LF . '<!-- ' . LF
            . $cssInlineFix
            . '-->' . LF . '/*]]>*/' . LF . '</style>' . LF;
    }

    /**
     * String 'FE' if in FrontendApplication, 'BE' otherwise (also in CLI without request object)
     *
     * @internal
     */
    public function getApplicationType(): string
    {
        if (
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface &&
            ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
        ) {
            return 'FE';
        }

        return 'BE';
    }
}
