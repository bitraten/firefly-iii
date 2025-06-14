<?php
/**
 * DuplicateTransactionRepository.php
 * Copyright (c) 2023 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see &lt;https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Repositories\Transaction;

use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DuplicateTransactionRepository
{
    private User $user;

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * Find potential duplicate transactions based on external_id
     */
    public function findDuplicatesByExternalId(): Collection
    {
        return DB::table('journal_meta')
            ->select([
                'journal_meta.id',
                'journal_meta.data as external_id',
                'transaction_journals.id as journal_id',
                'transaction_journals.date',
                'transaction_journals.description',
                'transaction_groups.id as group_id',
                DB::raw('COUNT(*) over (partition by journal_meta.data) as duplicate_count')
            ])
            ->join('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
            ->join('transaction_groups', 'transaction_groups.id', '=', 'transaction_journals.transaction_group_id')
            ->where('transaction_journals.user_id', $this->user->id)
            ->where('journal_meta.name', 'external_id')
            ->whereNull('journal_meta.deleted_at')
            ->whereNull('transaction_journals.deleted_at')
            ->whereNull('transaction_groups.deleted_at')
            ->havingRaw('COUNT(*) over (partition by journal_meta.data) > 1')
            ->orderBy('journal_meta.data')
            ->orderBy('transaction_journals.date')
            ->get();
    }

    /**
     * Delete a transaction journal
     */
    public function deleteTransactionJournal(int $journalId): void
    {
        /** @var TransactionJournal|null $journal */
        $journal = TransactionJournal::where('user_id', $this->user->id)
            ->where('id', $journalId)
            ->first();

        if ($journal !== null) {
            // Delete related metadata first
            TransactionJournalMeta::where('transaction_journal_id', $journal->id)->delete();
            
            // Delete the journal itself
            $journal->delete();
        }
    }
}
