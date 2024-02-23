<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Tests\Unit\LinkHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\LinkHandling\RecordLinkHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecordLinkHandlerTest extends UnitTestCase
{
    #[Test]
    public function asStringReturnsUrl(): void
    {
        $subject = new RecordLinkHandler();
        $parameters = [
            'identifier' => 'tx_identifier',
            'uid' => 123,
        ];
        $url = sprintf(
            't3://record?identifier=%s&uid=%s',
            $parameters['identifier'],
            $parameters['uid']
        );

        self::assertEquals($url, $subject->asString($parameters));
    }

    public static function missingParameterDataProvider(): array
    {
        return [
            'identifier is missing' => [
                [
                    'uid' => 123,
                ],
            ],
            'uid is missing' => [
                [
                    'identifier' => 'identifier',
                ],
            ],
        ];
    }

    #[DataProvider('missingParameterDataProvider')]
    #[Test]
    public function resolveHandlerDataThrowsExceptionIfParameterIsMissing(array $parameters): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1486155151);

        $subject = new RecordLinkHandler();
        $subject->resolveHandlerData($parameters);
    }

    #[DataProvider('missingParameterDataProvider')]
    #[Test]
    public function asStringThrowsExceptionIfParameterIsMissing(array $parameters): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1486155150);

        $subject = new RecordLinkHandler();
        $subject->asString($parameters);
    }
}
