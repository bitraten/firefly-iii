<?php

declare(strict_types=1);

namespace FireflyIII\Http\Controllers;

use FireflyIII\Repositories\Transaction\DuplicateTransactionRepository;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DuplicateTransactionController extends Controller
{
    private DuplicateTransactionRepository $repository;

    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->repository = app(DuplicateTransactionRepository::class);
                $this->repository->setUser(auth()->user());

                return $next($request);
            }
        );
    }

    /**
     * Show overview of duplicate transactions
     *
     * @return Factory|View
     */
    public function index()
    {
        $duplicates = $this->repository->findDuplicatesByExternalId();
        
        return view('transactions.duplicates.index', [
            'duplicates' => $duplicates,
            'title'      => (string)trans('firefly.duplicate_transactions'),
            'subTitle'   => (string)trans('firefly.duplicate_transactions_subtitle'),
        ]);
    }

    /**
     * Delete a duplicate transaction
     */
    public function delete(Request $request): JsonResponse
    {
        $journalId = (int)$request->get('journal_id');
        
        $this->repository->deleteTransactionJournal($journalId);
        
        return response()->json(['success' => true]);
    }
}
