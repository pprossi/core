<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Type;

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

use TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration;
use TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\CompleteEnumeration;
use TYPO3\CMS\Core\Tests\Unit\Type\Fixture\Enumeration\MissingDefaultEnumeration;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationDefinitionException;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class EnumerationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorThrowsExceptionIfNoConstantsAreDefined(): void
    {
        $this->expectException(InvalidEnumerationValueException::class);
        $this->expectExceptionCode(1381512753);

        new Enumeration\MissingConstantsEnumeration();
    }

    /**
     * @test
     */
    public function constructorThrowsExceptionIfInvalidValueIsRequested(): void
    {
        $this->expectException(InvalidEnumerationValueException::class);
        $this->expectExceptionCode(1381512761);

        new CompleteEnumeration('bar');
    }

    /**
     * @test
     */
    public function loadValuesThrowsExceptionIfGivenValueIsNotAvailableInEnumeration(): void
    {
        $this->expectException(InvalidEnumerationValueException::class);
        $this->expectExceptionCode(1381512807);

        new Enumeration\MissingConstantsEnumeration(2);
    }

    /**
     * @test
     */
    public function loadValuesThrowsExceptionIfDisallowedTypeIsDefinedAsConstant(): void
    {
        $this->expectException(InvalidEnumerationDefinitionException::class);
        $this->expectExceptionCode(1381512797);

        new Enumeration\InvalidConstantEnumeration(1);
    }

    /**
     * @test
     */
    public function loadValuesThrowsExceptionIfNoDefaultConstantIsDefinedAndNoValueIsGiven(): void
    {
        $this->expectException(InvalidEnumerationValueException::class);
        $this->expectExceptionCode(1381512753);

        new Enumeration\MissingDefaultEnumeration();
    }

    /**
     * @test
     */
    public function loadValuesThrowsExceptionIfValueIsDefinedMultipleTimes(): void
    {
        $this->expectException(InvalidEnumerationDefinitionException::class);
        $this->expectExceptionCode(1381512859);

        new Enumeration\DuplicateConstantValueEnumeration(1);
    }

    /**
     * Array of value pairs and expected comparison result
     * @return array
     */
    public function looseEnumerationValues(): array
    {
        return [
            [
                1,
                Enumeration\CompleteEnumeration::INTEGER_VALUE,
            ],
            [
                '1',
                Enumeration\CompleteEnumeration::INTEGER_VALUE,
            ],
            [
                2,
                Enumeration\CompleteEnumeration::STRING_INTEGER_VALUE,
            ],
            [
                '2',
                Enumeration\CompleteEnumeration::STRING_INTEGER_VALUE,
            ],
            [
                'foo',
                Enumeration\CompleteEnumeration::STRING_VALUE,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider looseEnumerationValues
     * @param $testValue
     * @param $expectedValue
     */
    public function doesTypeLooseComparison($testValue, $expectedValue): void
    {
        $value = new Enumeration\CompleteEnumeration($testValue);

        $this->assertEquals((string)$expectedValue, (string)$value);
    }

    /**
     * @test
     */
    public function getConstantsReturnsArrayOfPossibleValuesWithoutDefault(): void
    {
        $expected = [
            'INTEGER_VALUE' => 1,
            'STRING_INTEGER_VALUE' => '2',
            'STRING_VALUE' => 'foo',
        ];

        $this->assertEquals($expected, Enumeration\CompleteEnumeration::getConstants());
    }

    /**
     * @test
     */
    public function getConstantsReturnsArrayOfPossibleValuesWithDefaultIfRequested(): void
    {
        $expected = [
            'INTEGER_VALUE' => 1,
            'STRING_INTEGER_VALUE' => '2',
            'STRING_VALUE' => 'foo',
            '__default' => 1,
        ];

        $this->assertEquals($expected, Enumeration\CompleteEnumeration::getConstants(true));
    }

    /**
     * @test
     */
    public function getConstantsCanBeCalledOnInstances(): void
    {
        $enumeration = new Enumeration\CompleteEnumeration();
        $expected = [
            'INTEGER_VALUE' => 1,
            'STRING_INTEGER_VALUE' => '2',
            'STRING_VALUE' => 'foo',
        ];

        $this->assertEquals($expected, $enumeration::getConstants());
    }

    /**
     * @test
     */
    public function toStringReturnsValueAsString(): void
    {
        $enumeration = new CompleteEnumeration();
        $this->assertSame('1', $enumeration->__toString());
    }

    /**
     * @test
     */
    public function castReturnsObjectOfEnumerationTypeIfSimpleValueIsGiven(): void
    {
        $enumeration = CompleteEnumeration::cast(1);
        $this->assertInstanceOf(CompleteEnumeration::class, $enumeration);
    }

    /**
     * @test
     */
    public function castReturnsObjectOfCalledEnumerationTypeIfCalledWithValueOfDifferentType(): void
    {
        $initialEnumeration = new Enumeration\MissingDefaultEnumeration(1);
        $enumeration = CompleteEnumeration::cast($initialEnumeration);
        $this->assertInstanceOf(CompleteEnumeration::class, $enumeration);
    }

    /**
     * @test
     */
    public function castReturnsGivenObjectIfCalledWithValueOfSameType(): void
    {
        $initialEnumeration = new CompleteEnumeration(1);
        $enumeration = CompleteEnumeration::cast($initialEnumeration);
        $this->assertSame($initialEnumeration, $enumeration);
    }

    /**
     * @test
     */
    public function castCastsStringToEnumerationWithCorrespondingValue(): void
    {
        $value = new CompleteEnumeration(CompleteEnumeration::STRING_VALUE);

        $this->assertSame(CompleteEnumeration::STRING_VALUE, (string)$value);
    }

    /**
     * @test
     */
    public function castCastsIntegerToEnumerationWithCorrespondingValue(): void
    {
        $value = new CompleteEnumeration(CompleteEnumeration::INTEGER_VALUE);

        $this->assertSame((int)(string)CompleteEnumeration::INTEGER_VALUE, (int)(string)$value);
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfIntegerIsGivenThatEqualsEnumerationsIntegerValue(): void
    {
        $enumeration = new CompleteEnumeration(1);
        $this->assertTrue($enumeration->equals(1));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfStringIsGivenThatEqualsEnumerationsIntegerValue(): void
    {
        $enumeration = new CompleteEnumeration(1);
        $this->assertTrue($enumeration->equals('1'));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfEqualEnumerationIsGiven(): void
    {
        $enumerationFoo = new CompleteEnumeration(1);
        $enumerationBar = new CompleteEnumeration(1);
        $this->assertTrue($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function equalsReturnsTrueIfDifferentEnumerationWithSameValueIsGiven(): void
    {
        $enumerationFoo = new CompleteEnumeration(1);
        $enumerationBar = new MissingDefaultEnumeration(1);
        $this->assertTrue($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfDifferentEnumerationWithDifferentValueIsGiven(): void
    {
        $enumerationFoo = new CompleteEnumeration('foo');
        $enumerationBar = new MissingDefaultEnumeration(1);
        $this->assertFalse($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function equalsReturnsFalseIfEnumerationOfSameTypeWithDifferentValueIsGiven(): void
    {
        $enumerationFoo = new CompleteEnumeration(1);
        $enumerationBar = new CompleteEnumeration('foo');
        $this->assertFalse($enumerationFoo->equals($enumerationBar));
    }

    /**
     * @test
     */
    public function getNameProvidesNameForAvailableConstant(): void
    {
        $result = CompleteEnumeration::getName(CompleteEnumeration::INTEGER_VALUE);
        $this->assertSame('INTEGER_VALUE', $result);
    }

    /**
     * @test
     */
    public function getNameReturnsEmptyStringForNotAvailableConstant(): void
    {
        $result = CompleteEnumeration::getName(42);
        $this->assertSame('', $result);
    }

    /**
     * @test
     */
    public function getHumanReadableNameProvidesNameForAvailableConstant(): void
    {
        $result = CompleteEnumeration::getHumanReadableName(CompleteEnumeration::INTEGER_VALUE);
        $this->assertSame('Integer Value', $result);
    }

    /**
     * @test
     */
    public function getHumanReadableNameReturnsEmptyStringForNotAvailableConstant(): void
    {
        $result = CompleteEnumeration::getName(42);
        $this->assertSame('', $result);
    }
}
