<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\BankAccount;
use App\Imports\JournalImport;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\TransactionLines;
use App\Models\Utility;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{

    public function index()
    {
        if (\Auth::user()->can('manage journal entry')) {
            $journalEntries = JournalEntry::where('created_by', '=', \Auth::user()->creatorId())->get();

            return view('journalEntry.index', compact('journalEntries'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function create()
    {
        if (\Auth::user()->can('create journal entry')) {
            $accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))->where('created_by', \Auth::user()->creatorId())->get()->pluck('code_name', 'id');
            $accounts->prepend('--', '');

            $journalId = $this->journalNumber();

            return view('journalEntry.create', compact('accounts', 'journalId'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function store(Request $request)
    {

        if (\Auth::user()->can('create invoice')) {
            $validator = \Validator::make(
                $request->all(),
                [
                    'date' => 'required',
                    'accounts' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $accounts = $request->accounts;

            $totalDebit  = 0;
            $totalCredit = 0;
            for ($i = 0; $i < count($accounts); $i++) {
                $debit       = isset($accounts[$i]['debit']) ? (int)$accounts[$i]['debit'] : 0;
                $credit      = isset($accounts[$i]['credit']) ? (int)$accounts[$i]['credit'] : 0;
                $totalDebit  += $debit;
                $totalCredit += $credit;
               
            }

            if ($totalCredit != $totalDebit) {
                return redirect()->back()->with('error', __('Debit and Credit must be Equal.'));
            }

            $journal              = new JournalEntry();
            $journal->journal_id  = $this->journalNumber();
            $journal->date        = $request->date;
            $journal->reference   = $request->reference;
            $journal->description = $request->description;
            $journal->created_by  = \Auth::user()->creatorId();
            $journal->save();


            for ($i = 0; $i < count($accounts); $i++) {
                $journalItem              = new JournalItem();
                $journalItem->journal     = $journal->id;
                $journalItem->account     = $accounts[$i]['account'];
                $journalItem->description = $accounts[$i]['description'];
                $journalItem->debit       = isset($accounts[$i]['debit']) ? $accounts[$i]['debit'] : 0;
                $journalItem->credit      = isset($accounts[$i]['credit']) ? $accounts[$i]['credit'] : 0;
                $journalItem->save();

                $bankAccounts = BankAccount::where('chart_account_id', '=', $accounts[$i]['account'])->get();
                if (!empty($bankAccounts)) {
                    foreach ($bankAccounts as $bankAccount) {
                        $old_balance = $bankAccount->opening_balance;
                        if ($journalItem->debit > 0) {
                            $new_balance = $old_balance - $journalItem->debit;
                        }
                        if ($journalItem->credit > 0) {
                            $new_balance = $old_balance + $journalItem->credit;
                        }
                        if (isset($new_balance)) {
                            $bankAccount->opening_balance = $new_balance;
                            $bankAccount->save();
                        }
                    }
                }
                if (isset($accounts[$i]['debit'])) {
                    $data = [
                        'account_id' => $accounts[$i]['account'],
                        'transaction_type' => 'Debit',
                        'transaction_amount' => $accounts[$i]['debit'],
                        'reference' => 'Journal',
                        'reference_id' => $journal->id,
                        'reference_sub_id' => $journalItem->id,
                        'date' => $journal->date,
                    ];
                } else {
                    $data = [
                        'account_id' => $accounts[$i]['account'],
                        'transaction_type' => 'Credit',
                        'transaction_amount' => $accounts[$i]['credit'],
                        'reference' => 'Journal',
                        'reference_id' => $journal->id,
                        'reference_sub_id' => $journalItem->id,
                        'date' => $journal->date,
                    ];
                }
                Utility::addTransactionLines($data);
            }

            return redirect()->route('journal-entry.index')->with('success', __('Journal entry successfully created.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
    
    public function importFile()
    {
        $accounts = ChartOfAccount::all()->toArray();
        $journalEntries = JournalEntry::all()->toArray();
        array_unshift($journalEntries, ['id' => '', 'name' => 'Select Journal ID']);
        return view('journalEntry.import', ['accounts' => $accounts, 'journals' => $journalEntries]);
    }
    
    public function import(Request $request)
    {
        // dd($request);
        if (!\Auth::user()->can('create journal entry')) {
            return redirect()->back()->with('error', 'Permission Denied');
        }
        $rules = [
            'file' => 'required|mimes:csv,txt,xls',
            'description' => 'string|nullable',
            'reference' => 'string|nullable',
            'credit_account' => 'string',
            'debit_account' => 'string',
            'add_to_journal_id' => 'string|nullable'
        ];

        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $journals   = (new JournalImport())->toArray($request->file('file'))[0];
        // DATE DESCRIPTION ACCOUNT DEBIT CREDIT
        
        // CREATE A JOURNAL
        // $journal = new JournalEntry();
        
        // ensure debit and credit are equal
        $totalDebit = 0;
        $totalCredit = 0;
        $journal = null;

        if(!$request->add_to_journal_id == null){
            $journal = JournalEntry::where('id', (int)$request->add_to_journal_id)->first();
        } else {
            $journal = new JournalEntry();
            $journal->journal_id  = $this->journalNumber();
            $journal->date        = Carbon::now()->format('Y-m-d');
            $journal->reference   = $request->reference ?? "No Reference";
            $journal->description = $request->description ?? "No Description";
            $journal->created_by  = \Auth::user()->creatorId();
            $journal->save();
        }
        
        for ($i = 1; $i <= count($journals) - 1; $i++) {
            $debit       = isset($journals[$i][3]) ? (int)str_replace(',', '', $journals[$i][3]) : 0;
            $credit      = isset($journals[$i][4]) ? (int)str_replace(',', '', $journals[$i][4]) : 0;
            $totalDebit  += $debit;
            $totalCredit += $credit;
            
            $journalItem              = new JournalItem();
            $journalItem->journal     = $journal->id;
            $journalItem->date = Carbon::createFromFormat('n/j/Y', $journals[$i][0])->format('Y-m-d');
            if(isset($journals[$i][3])) { // THIS IS A DEBIT TRANSACTION, SET ACCOUNT TO THE DEBIT ACCOUNT
                $journalItem->account = (int)$request->debit_account;
                $bankAccounts = BankAccount::where('chart_account_id', '=', $request->debit_account)->get();
            } else if(isset($journals[$i][4])) { // THIS IS A CREDIT TRANSACTION, SET ACCOUNT TO THE CREDIT ACCOUNT;
                $journalItem->account = (int)$request->credit_account;
                $bankAccounts = BankAccount::where('chart_account_id', '=', $request->credit_account)->get();
            }
            $journalItem->description = $journals[$i][1];
            $journalItem->debit       = isset($journals[$i][3]) ? (int)str_replace(',', '', $journals[$i][3]) : 0;
            $journalItem->credit      = isset($journals[$i][4]) ? (int)str_replace(',', '', $journals[$i][4]) : 0;
            $journalItem->save();
            
            // $bankAccounts = BankAccount::where('chart_account_id', '=', $accounts[$i]['account'])->get();
            if (!empty($bankAccounts)) {
                foreach ($bankAccounts as $bankAccount) {
                    $old_balance = $bankAccount->opening_balance;
                    if ($journalItem->debit > 0) {
                        $new_balance = $old_balance - $journalItem->debit;
                    }
                    if ($journalItem->credit > 0) {
                        $new_balance = $old_balance + $journalItem->credit;
                    }
                    if (isset($new_balance)) {
                        $bankAccount->opening_balance = $new_balance;
                        $bankAccount->save();
                    }
                }
            }
            if (isset($journals[$i][3])) {
                $data = [
                    'account_id' => $request->debit_account,
                    'transaction_type' => 'Debit',
                    'transaction_amount' => $journals[$i][3],
                    'reference' => 'Journal',
                    'reference_id' => $journal->id,
                    'reference_sub_id' => $journalItem->id,
                    'date' => $journal->date,
                ];
            } else {
                $data = [
                    'account_id' => $request->credit_account,
                    'transaction_type' => 'Credit',
                    'transaction_amount' => $journals[$i][4],
                    'reference' => 'Journal',
                    'reference_id' => $journal->id,
                    'reference_sub_id' => $journalItem->id,
                    'date' => $journal->date,
                ];
            }
            Utility::addTransactionLines($data);
        }
        
        // if ($totalCredit != $totalDebit) {
        //     return redirect()->back()->with('error', __('Total Debit and Total Credit must be Equal.'));
        // }

        return redirect()->route('journal-entry.index')->with('success', __('Journal entry successfully created.'));
        
        // $str = "total credit " . $totalCredit . " total debit " . $totalDebit;
        // dd($str);
        
        
        $errorArray = [];
    }


    public function show(JournalEntry $journalEntry)
    {
        if (\Auth::user()->can('show journal entry')) {
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                $accounts = $journalEntry->accounts;
                $settings = Utility::settings();

                return view('journalEntry.view', compact('journalEntry', 'accounts', 'settings'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function edit(JournalEntry $journalEntry)
    {
        if (\Auth::user()->can('edit journal entry')) {
            $accounts = ChartOfAccount::select(\DB::raw('CONCAT(code, " - ", name) AS code_name, id'))->where('created_by', \Auth::user()->creatorId())->get()->pluck('code_name', 'id');
            $accounts->prepend('--', '');

            return view('journalEntry.edit', compact('accounts', 'journalEntry'));
        } else {
            return response()->json(['error' => __('Permission denied.')], 401);
        }
    }


    public function update(Request $request, JournalEntry $journalEntry)
    {
        if (\Auth::user()->can('edit journal entry')) {
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                $validator = \Validator::make(
                    $request->all(),
                    [
                        'date' => 'required',
                        'accounts' => 'required',
                    ]
                );
                if ($validator->fails()) {
                    $messages = $validator->getMessageBag();

                    return redirect()->back()->with('error', $messages->first());
                }

                $accounts = $request->accounts;

                $totalDebit  = 0;
                $totalCredit = 0;
                for ($i = 0; $i < count($accounts); $i++) {
                    $debit = isset($accounts[$i]['debit']) ? $accounts[$i]['debit'] : 0;
                    $credit = isset($accounts[$i]['credit']) ? $accounts[$i]['credit'] : 0;
                    $totalDebit += $debit;
                    $totalCredit += $credit;
                }

                if ($totalCredit != $totalDebit) {
                    return redirect()->back()->with('error', __('Debit and Credit must be Equal.'));
                }
                
                for ($i = 0; $i < count($accounts); $i++) {
                    $journalItem = JournalItem::find($accounts[$i]['id']);

                    if ($journalItem == null) {
                        $journalItem = new JournalItem();
                        $journalItem->journal = $journalEntry->id;
                    }

                    if (isset($accounts[$i]['account'])) {
                        $journalItem->account = $accounts[$i]['account'];
                    }

                    $journalItem->description = $accounts[$i]['description'];
                    $journalItem->debit = isset($accounts[$i]['debit']) ? $accounts[$i]['debit'] : 0;
                    $journalItem->credit = isset($accounts[$i]['credit']) ? $accounts[$i]['credit'] : 0;
                    $journalItem->save();

                    $bankAccounts = BankAccount::where('chart_account_id', '=', $accounts[$i]['account'])->get();
                    if (!empty($bankAccounts)) {
                        foreach ($bankAccounts as $bankAccount) {
                            $old_balance = $bankAccount->opening_balance;
                            if ($journalItem->debit > 0) {
                                $new_balance = $old_balance - $journalItem->debit;
                            }
                            if ($journalItem->credit > 0) {
                                $new_balance = $old_balance + $journalItem->credit;
                            }
                            if (isset($new_balance)) {
                                $bankAccount->opening_balance = $new_balance;
                                $bankAccount->save();
                            }
                        }
                    }

                    if (isset($accounts[$i]['debit'])) {
                        $data = [
                            'account_id' => $accounts[$i]['account'],
                            'transaction_type' => 'Debit',
                            'transaction_amount' => $accounts[$i]['debit'],
                            'reference' => 'Journal',
                            'reference_id' => $journalEntry->id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $journalEntry->date,
                        ];
                    } else {
                        $data = [
                            'account_id' => $accounts[$i]['account'],
                            'transaction_type' => 'Credit',
                            'transaction_amount' => $accounts[$i]['credit'],
                            'reference' => 'Journal',
                            'reference_id' => $journalEntry->id,
                            'reference_sub_id' => $journalItem->id,
                            'date' => $journalEntry->date,
                        ];
                    }
                    Utility::addTransactionLines($data);
                }

               

                $journalEntry->date        = $request->date;
                $journalEntry->reference   = $request->reference;
                $journalEntry->description = $request->description;
                $journalEntry->created_by  = \Auth::user()->creatorId();
                $journalEntry->save();

                for ($i = 0; $i < count($accounts); $i++) {
                    $journalItem = JournalItem::find($accounts[$i]['id']);

                    if ($journalItem == null) {
                        $journalItem          = new JournalItem();
                        $journalItem->journal = $journalEntry->id;
                    }

                    if (isset($accounts[$i]['account'])) {
                        $journalItem->account = $accounts[$i]['account'];
                    }

                    $journalItem->description = $accounts[$i]['description'];
                    $journalItem->debit  = isset($accounts[$i]['debit']) ? $accounts[$i]['debit'] : 0;
                    $journalItem->credit = isset($accounts[$i]['credit']) ? $accounts[$i]['credit'] : 0;
                    $journalItem->save();
                }

                return redirect()->route('journal-entry.index')->with('success', __('Journal entry successfully updated.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }


    public function destroy(JournalEntry $journalEntry)
    {
        if (\Auth::user()->can('delete journal entry')) {
            if ($journalEntry->created_by == \Auth::user()->creatorId()) {
                $journalEntry->delete();

                JournalItem::where('journal', '=', $journalEntry->id)->delete();

                TransactionLines::where('reference_id', $journalEntry->id)->where('reference', 'Journal')->delete();

                return redirect()->route('journal-entry.index')->with('success', __('Journal entry successfully deleted.'));
            } else {
                return redirect()->back()->with('error', __('Permission denied.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }

    public function journalDestroy($item_id)
    {
        $journal = JournalItem::find($item_id);
        $journal->delete();

        return redirect()->back()->with('success', __('Journal successfully deleted.'));
    }

    function journalNumber()
    {
        $latest = JournalEntry::where('created_by', '=', \Auth::user()->creatorId())->latest()->first();
        if (!$latest) {
            return 1;
        }

        return $latest->journal_id + 1;
    }

    public function accountDestroy(Request $request)
    {

        if (\Auth::user()->can('delete journal entry')) {
            JournalItem::where('id', '=', $request->id)->delete();

            return redirect()->back()->with('success', __('Journal entry account successfully deleted.'));
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
