<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Page\Event;

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

use TYPO3\CMS\Core\Page\AssetCollector;

/**
 * This event is fired once before \TYPO3\CMS\Core\Page\AssetRenderer::render[Inline]JavaScript renders the output.
 */
final class BeforeJavaScriptsRenderingEvent extends AbstractBeforeAssetRenderingEvent
{
    public function __construct(AssetCollector $assetCollector, bool $isInline, bool $priority)
    {
        $this->assetCollector = $assetCollector;
        $this->inline = $isInline;
        $this->priority = $priority;
    }
}
