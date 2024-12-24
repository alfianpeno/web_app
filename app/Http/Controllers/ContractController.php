<?php

namespace App\Http\Controllers;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\ContractAttachment;
use App\Models\ContractComment;
use App\Models\ContractNote;
use App\Models\User;
use App\Models\UserDefualtView;
use App\Models\Utility;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;



class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        if ((\Auth::user()->can('manage contract')) || (\Auth::user()->can('manage customer contract')))
        {
            if(\Auth::user()->type == 'company')
            {

                $contracts = Contract::where('created_by', '=', \Auth::user()->creatorId())->get();
                $curr_month = Contract::where('created_by', '=', \Auth::user()->creatorId())->whereMonth('start_date', '=', date('m'))->get();
                $curr_week = Contract::where('created_by', '=', \Auth::user()->creatorId())->whereBetween(
                    'start_date', [
                       \carbon\Carbon::now()->startOfWeek(),
                        \carbon\Carbon::now()->endOfWeek(),
                    ]
                )->get();
                $last_30days = Contract::where('created_by', '=', \Auth::user()->creatorId())->whereDate('start_date', '>', \Carbon\Carbon::now()->subDays(30))->get();
            }
            else
            {

                $contracts = Contract::where('customer', '=', \Auth::user()->id)->get();

                $curr_month = Contract::where('customer', '=', \Auth::user()->id)->whereMonth('start_date', '=', date('m'))->get();
                $curr_week = Contract::where('customer', '=', \Auth::user()->id)->whereBetween(
                    'start_date', [
                        \Carbon\Carbon::now()->startOfWeek(),
                        \Carbon\Carbon::now()->endOfWeek(),
                    ]
                )->get();
                $last_30days = Contract::where('customer', '=', \Auth::user()->id)->whereDate('start_date', '>', \Carbon\Carbon::now()->subDays(30))->get();
            }

            $cnt_contract                = [];
            $cnt_contract['total']       = \App\Models\Contract::getContractSummary($contracts);
            $cnt_contract['this_month']  = \App\Models\Contract::getContractSummary($curr_month);
            $cnt_contract['this_week']   = \App\Models\Contract::getContractSummary($curr_week);
            $cnt_contract['last_30days'] = \App\Models\Contract::getContractSummary($last_30days);

            return view('contract.index', compact('contracts','cnt_contract'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied.'));
        }


    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $contractTypes = ContractType::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $contractTypes->prepend('Select Contract Types', '');
        $customers       = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $customers->prepend('Select Customer' , '');

        return view('contract.create', compact('contractTypes', 'customers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(\Auth::user()->can('create contract'))
        {
            $rules = [
                'customer' => 'required',
                'subject' => 'required',
                'type' => 'required',
                'value' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'edit_status' => 'Pending',
            ];

            $validator = \Validator::make($request->all(), $rules);
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->route('contract.index')->with('error', $messages->first());
            }

            $contract              = new Contract();
            $contract->customer      = $request->customer;
            $contract->subject     = $request->subject;
            $contract->type        = $request->type;
            $contract->value       = $request->value;
            $contract->start_date  = $request->start_date;
            $contract->end_date    = $request->end_date;
            $contract->description = $request->description;
            $contract->created_by  = \Auth::user()->creatorId();
            $contract->save();

            return redirect()->route('contract.index')->with('success', __('Contract successfully created.'));

        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        $contract = Contract::find($id);
        if(\Auth::user()->can('show contract'))
        {
            return view('contract.view', compact('contract'));
        }
        else
        {
            return redirect()->back()->with('error', 'permission Denied');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Contract $contract)
    {
        $contractTypes = ContractType::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $customers       = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

        return view('contract.edit', compact('contractTypes', 'customers', 'contract'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contract $contract)
    {

        if(\Auth::user()->can('edit contract'))
        {
            $rules = [
                'customer' => 'required',
                'subject' => 'required',
                'type' => 'required',
                'value' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
            ];

            $validator = \Validator::make($request->all(), $rules);

            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->route('contract.index')->with('error', $messages->first());
            }

            $contract->customer      = $request->customer;
            $contract->subject     = $request->subject;
            $contract->type        = $request->type;
            $contract->value       = $request->value;
            $contract->start_date  = $request->start_date;
            $contract->end_date    = $request->end_date;
            $contract->description = $request->description;

            $contract->save();

            return redirect()->route('contract.index')->with('success', __('Contract successfully updated.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function destroy(Contract $contract)
    // {
    //     if(\Auth::user()->can('delete contract'))
    //     {
    //         $contract->delete();

    //         return redirect()->route('contract.index')->with('success', __('Contract successfully deleted.'));
    //     }
    //     else
    //     {
    //         return redirect()->back()->with('error', __('Permission denied.'));
    //     }
    // }

    public function destroy($id)
    {
        if(\Auth::user()->can('delete contract'))
        {
            $contract =Contract::find($id);
            // if($contract->created_by == \Auth::user()->id)
            // {

                $attechments = $contract->ContractAttachment()->get()->each;

                foreach($attechments->items as $attechment){
                    if (\Storage::exists('contract_attachment/'.$attechment->files)) {
                            unlink('storage/contract_attachment/'.$attechment->files);
                    }
                    $attechment->delete();
                }

                $contract->ContractComment()->get()->each->delete();
                $contract->ContractNote()->get()->each->delete();
                $contract->delete();

                return redirect()->route('contract.index')->with('success', __('Contract successfully deleted!'));
            // }
            // else
            // {
            //     return redirect()->back()->with('error', __('Permission Denied.'));
            // }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function duplicate(Contract $contract,$id)
    {
        $contract = Contract::find($id);
        $contractTypes = ContractType::where('created_by', '=', \Auth::user()->creatorId())->get()->pluck('name', 'id');
        $customers       = Customer::where('created_by', \Auth::user()->creatorId())->get()->pluck('name', 'id');

        return view('contract.duplicate', compact('contractTypes', 'customers', 'contract'));
    }

    public function duplicatecontract(Request $request)
    {
        if(\Auth::user()->can('create contract'))
        {
            $rules = [
                'customer' => 'required',
                'subject' => 'required',
                'type' => 'required',
                'value' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
            ];

            $validator = \Validator::make($request->all(), $rules);
            if($validator->fails())
            {
                $messages = $validator->getMessageBag();

                return redirect()->route('contract.index')->with('error', $messages->first());
            }

            $contract              = new Contract();
            $contract->customer      = $request->customer;
            $contract->subject     = $request->subject;
            $contract->type        = $request->type;
            $contract->value       = $request->value;
            $contract->start_date  = $request->start_date;
            $contract->end_date    = $request->end_date;
            $contract->description = $request->description;
            $contract->created_by  = \Auth::user()->creatorId();
            $contract->save();

            return redirect()->route('contract.index')->with('success', __('Duplicate Contract successfully created.'));
        }
    }

    public function descriptionStore($id, Request $request)
    {

        if (\Auth::user()->can('contract description'))
        {
            $contract        =Contract::find($id);
            $contract->notes = $request->notes;
            $contract->save();
            return redirect()->back()->with('success', __('Contract Description successfully saved.'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission denied'));
        }
    }

    public function fileUpload($id, Request $request)
    {
        if(\Auth::guard('customer')->check()){
            $user_type = 'customer';
        }else{
            $user_type = 'company';
        }

        $contract = Contract::find($id);
        if ((\Auth::user()->can('upload attachment')))
        {
            $request->validate(['file' => 'required']);
            // $files = $request->file->getClientOriginalName();
            // $request->file->storeAs('contract_attachment', $files);



            $dir = 'contract_attachment/';
            $files = $request->file->getClientOriginalName();
            $path = Utility::upload_file($request,'file',$files,$dir,[]);
                if($path['flag'] == 1){
                    $file = $path['url'];
                }
                else{
                    return redirect()->back()->with('error', __($path['msg']));
                }




            $file                 = ContractAttachment::create(
                [
                    'contract_id' => $request->contract_id,
                    'created_by' => \Auth::user()->id,
                    'files' => $files,
                    'type'=>$user_type,
                ]
            );
            $return               = [];
            $return['is_success'] = true;
            $return['download']   = route(
                'contract.file.download', [
                                         $contract->id,
                                         $file->id,
                                     ]
            );
            $return['delete']     = route(
                'contract.file.delete', [
                                       $contract->id,
                                       $file->id,
                                   ]
            );
            return response()->json($return);
        }
        else
            {
                return response()->json(
                    [
                        'is_success' => false,
                        'error' => __('Permission Denied.'),
                    ], 401
                );
            }
    }

    public function fileDownload($id, $file_id)
    {
        if(\Auth::user()->type == 'company')
        {
            $contract = Contract::find($id);
            if($contract->created_by == \Auth::user()->creatorId())
            {
                $file = ContractAttachment::find($file_id);
                if($file)
                {
                    $file_path = storage_path('contract_attachment/' . $file->files);
                    return \Response::download(
                        $file_path, $file->files, [
                                      'Content-Length: ' . filesize($file_path),
                                  ]
                    );
                }
                else
                {
                    return redirect()->back()->with('error', __('File is not exist.'));
                }
            }
            else
            {
                return redirect()->back()->with('error', __('Permission Denied.'));
            }
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }

    public function fileDelete($id, $file_id)
    {
        $contract = Contract::find($id);
        $file = ContractAttachment::find($file_id);
        if($file)
        {
            $path = storage_path('contract_attachment/' . $file->files);
            if(file_exists($path))
            {
                \File::delete($path);
            }
            $file->delete();

            return redirect()->back()->with('success', __('Attachment successfully deleted!'));

        }
        else
        {
            return response()->json(
                [
                    'is_success' => false,
                    'error' => __('File is not exist.'),
                ], 200
            );
        }
    }

    public function commentStore(Request $request ,$id)
    {
        if(\Auth::guard('customer')->check()){
            $user_type = 'customer';
        }else{
            $user_type = 'company';
        }

        $contract = Contract::find($id);
        if($contract->edit_status == 'accept'){
            $contract              = new ContractComment();
            $contract->comment     = $request->comment;
            $contract->contract_id = $id;
            $contract->created_by     = \Auth::user()->id;
            $contract->type     = $user_type;
            $contract->save();

            return redirect()->back()->with('success', __('Comment Added Successfully!'));
        }
        else{
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }

    public function commentDestroy($id)
    {
        $contract = ContractComment::find($id);
        if((\Auth::user()->can('delete comment')) || (\Auth::user()->type != 'company'))
        {
            $contract->delete();
            return redirect()->back()->with('success', __('Comment successfully deleted!'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function noteStore($id, Request $request)
    {
        $rules = [
            'note' => 'required'
        ];

        $validator = \Validator::make($request->all(), $rules);
        if($validator->fails())
        {
            $messages = $validator->getMessageBag();

            return redirect()->route('contract.index')->with('error', $messages->first());
        }

        if(\Auth::guard('customer')->check()){
            $user_type = 'customer';
        }else{
            $user_type = 'company';
        }

        $contract              = Contract::find($id);
        $contract              = new ContractNote();
        $contract->contract_id    = $id;
        $contract->note           = $request->note;
        $contract->created_by     = \Auth::user()->id;
        $contract->type     = $user_type;
        $contract->save();
        return redirect()->back()->with('success', __('Note successfully saved.'));
    }

    public function noteDestroy($id)
    {
        $contract = ContractNote::find($id);
        if((\Auth::user()->can('delete notes')) || (\Auth::user()->type != 'company'))
        {
            $contract->delete();
            return redirect()->back()->with('success', __('Notes successfully deleted!'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }

    }


    public function pdffromcontract($contract_id)
    {
        $id      = Crypt::decrypt($contract_id);
        $contract  = Contract::findOrFail($id);
        $settings = Utility::settings();

        if(\Auth::check())
        {
            $usr=\Auth::user();

        }
        else
        {
            $usr=Customer::where('id',$contract->created_by)->first();
        }

        if($contract)
        {
            $color      = '#' . $settings['invoice_color'];
            $font_color = Utility::getFontColor($color);
            return view('contract.templates.' . $settings['contract_template'], compact('contract', 'color', 'settings', 'font_color','usr'));
        }
        else
        {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function printContract($id)
    {
        $contract  = Contract::findOrFail($id);
        $settings = Utility::settings();

        return view('contract.contract_view', compact('contract','settings'));

    }

    public function signature($id)
    {
        $contract = Contract::find($id);

        return view('contract.signature', compact('contract'));
    }

    public function signatureStore(Request $request)
    {

        $contract              = Contract::find($request->contract_id);

        if(\Auth::user()->type == 'company'){
            $contract->company_signature       = $request->company_signature;
        }
        else{
            $contract->customer_signature       = $request->customer_signature;
        }

        $contract->save();

        return response()->json(
            [
                'Success' => true,
                'message' => __('Contract Signed successfully'),
            ], 200
        );

    }



    public function sendmailContract($id,Request $request)
    {
        $contract              = Contract::find($id);
        $contractArr = [
            'contract_id' => $contract->id,
        ];

        $customers = customer::find($contract->clients);

        $uArr = [
            'email' => $contract->clients->email,
            'contract_subject' => $contract->subject,
            'contract_customer' => $contract->clients->name,
            'contract_type' =>$contract->types->name,
            'contract_value' => $contract->value,
            'contract_start_date' => $contract->start_date,
            'contract_end_date' =>$contract->end_date ,

        ];

        $resp = Utility::sendEmailTemplate('new_contract', [$contract->clients->customer_id => $contract->clients->email], $uArr);
    // dd([$contract->clients->customer_id => $contract->clients->email]);
        return redirect()->route('contract.show', $contract->id)->with('success', __('Email Send successfully!') . (($resp['is_success'] == false && !empty($resp['error'])) ? '<br> <span class="text-danger">' . $resp['error'] . '</span>' : ''));
    }


    public function contract_status_edit(Request $request, $id)
    {
        $contract = Contract::find($id);
        $contract->edit_status   = $request->edit_status;
        $contract->save();

    }


}
