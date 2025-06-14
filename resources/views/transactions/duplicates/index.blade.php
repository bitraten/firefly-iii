@extends('layouts.default')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ trans('firefly.duplicate_transactions') }}</h3>
                </div>
                <div class="card-body">
                    @if($duplicates->count() > 0)
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('list.external_id') }}</th>
                                    <th>{{ trans('list.date') }}</th>
                                    <th>{{ trans('list.description') }}</th>
                                    <th>{{ trans('list.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $currentExternalId = null;
                                @endphp
                                @foreach($duplicates as $duplicate)
                                    @if($currentExternalId !== $duplicate->external_id)
                                        @php
                                            $currentExternalId = $duplicate->external_id;
                                        @endphp
                                        <tr>
                                            <td colspan="4" class="bg-light">
                                                <strong>{{ trans('firefly.external_id') }}: {{ $duplicate->external_id }}</strong>
                                                ({{ trans_choice('firefly.duplicate_count', $duplicate->duplicate_count, ['count' => $duplicate->duplicate_count]) }})
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td></td>
                                        <td>{{ $duplicate->date }}</td>
                                        <td>{{ $duplicate->description }}</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm delete-duplicate" 
                                                    data-journal-id="{{ $duplicate->journal_id }}"
                                                    title="{{ trans('firefly.delete_transaction') }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>{{ trans('firefly.no_duplicate_transactions') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function() {
            $('.delete-duplicate').on('click', function() {
                const button = $(this);
                const journalId = button.data('journal-id');
                
                if (confirm('{{ trans('firefly.confirm_delete_transaction') }}')) {
                    $.ajax({
                        url: '{{ route('transactions.duplicates.delete') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            journal_id: journalId
                        },
                        success: function() {
                            // Remove the row from the table
                            button.closest('tr').remove();
                        },
                        error: function() {
                            alert('{{ trans('firefly.error_deleting_transaction') }}');
                        }
                    });
                }
            });
        });
    </script>
@endsection
