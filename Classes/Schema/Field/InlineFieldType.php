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

namespace TYPO3\CMS\Core\Schema\Field;

use TYPO3\CMS\Core\Schema\RelationshipType;

/**
 * This is a "inline" reference field - the "parent" field to a child table / field.
 *
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
final readonly class InlineFieldType extends AbstractFieldType implements FieldTypeInterface, RelationalFieldTypeInterface
{
    public function __construct(
        protected string $name,
        protected array $configuration,
        protected array $relations,
    ) {}

    public function getType(): string
    {
        return 'inline';
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    public function getRelationshipType(): RelationshipType
    {
        return RelationshipType::fromTcaConfiguration($this->configuration);
    }

    public function isMovingChildrenEnabled(): bool
    {
        return (bool)($this->configuration['behaviour']['disableMovingChildrenWithParent'] ?? false) === false;
    }

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }
}
