<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Tables\Tests\Unit\Model;

use InvalidArgumentException;
use OCA\Tables\Model\RowDataInput;
use PHPUnit\Framework\TestCase;

class RowDataInputTest extends TestCase {

	public function testParsesDocumentedColumnIdMap(): void {
		$rowData = RowDataInput::fromApiInput(['18' => 'updated value', '23' => null]);

		$this->assertSame([
			['columnId' => 18, 'value' => 'updated value'],
			['columnId' => 23, 'value' => null],
		], $rowData->toArray());
	}

	public function testParsesEmptyInput(): void {
		$this->assertSame([], RowDataInput::fromApiInput([])->toArray());
	}

	public function testParsesSupportedScalarValues(): void {
		$rowData = RowDataInput::fromApiInput([
			'18' => false,
			'19' => 0,
			'20' => null,
			'21' => '',
		]);

		$this->assertSame([
			['columnId' => 18, 'value' => false],
			['columnId' => 19, 'value' => 0],
			['columnId' => 20, 'value' => null],
			['columnId' => 21, 'value' => ''],
		], $rowData->toArray());
	}

	public function testPreservesWebClientListFormat(): void {
		$rowData = RowDataInput::fromApiInput([
			['columnId' => 18, 'value' => 'updated value'],
			['columnId' => '23', 'value' => ['admin']],
		]);

		$this->assertSame([
			['columnId' => 18, 'value' => 'updated value'],
			['columnId' => 23, 'value' => ['admin']],
		], $rowData->toArray());
	}

	public function testPreservesRepeatedColumnIdsInListFormat(): void {
		$rowData = RowDataInput::fromApiInput([
			['columnId' => 18, 'value' => 'first value'],
			['columnId' => 18, 'value' => 'second value'],
		]);

		$this->assertSame([
			['columnId' => 18, 'value' => 'first value'],
			['columnId' => 18, 'value' => 'second value'],
		], $rowData->toArray());
	}

	public function testRejectsInvalidColumnIdInMap(): void {
		$this->expectException(InvalidArgumentException::class);

		RowDataInput::fromApiInput(['not-a-column' => 'value']);
	}

	public function testRejectsZeroAndNegativeColumnIds(): void {
		foreach ([0, -1, '0', '-1'] as $columnId) {
			$exceptionThrown = false;
			try {
				RowDataInput::fromApiInput([$columnId => 'value']);
			} catch (InvalidArgumentException) {
				$exceptionThrown = true;
			}
			$this->assertTrue($exceptionThrown, 'Expected invalid column ID to be rejected.');
		}
	}

	public function testRejectsListEntryWithoutColumnIdOrValue(): void {
		$this->expectException(InvalidArgumentException::class);

		RowDataInput::fromApiInput([['columnId' => 18]]);
	}

	public function testRejectsUnsupportedValueTypes(): void {
		$this->expectException(InvalidArgumentException::class);

		RowDataInput::fromApiInput(['18' => new \stdClass()]);
	}
}
