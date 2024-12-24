<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Calculation\Web;

class WebhookController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        $module = [
            'New Customer' => 'New Customer', 'New Invoice' => 'New Invoice', 'New Bill' => 'New Bill', 'New Vendor' => 'New Vendor', 'New Revenue' => 'New Revenue', 'New Proposal' => 'New Proposal', 'New Payment' => 'New Payment', 'Invoice Reminder' => 'Invoice Reminder'
        ];

        $method = ['Get' => 'Get', 'Post' => 'Post'];

        return view('webhook.create', compact('module', 'method'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $validator = \Validator::make(
            $request->all(),
            [
                'module' => 'required',
                'method' => 'required',
                'url' => 'required',
            ]
        );
        if ($validator->fails()) {
            $messages = $validator->getMessageBag();

            return redirect()->back()->with('error', $messages->first());
        }

        $assets          = new Webhook();
        $assets->module  = $request->module;
        $assets->method  = $request->method;
        $assets->url     = $request->url;
        $assets->created_by     = \Auth::user()->creatorId();
        $assets->save();

        return redirect()->back()->with('success', __('Webhook Successfully created.'));
    }

    public function edit($id)
    {
        $webhook = Webhook::find($id);
        $module = [
            'New Customer' => 'New Customer', 'New Invoice' => 'New Invoice', 'New Bill' => 'New Bill', 'New Vendor' => 'New Vendor', 'New Revenue' => 'New Revenue', 'New Proposal' => 'New Proposal', 'New Payment' => 'New Payment', 'Invoice Reminder' => 'Invoice Reminder'
        ];

        $method = ['Get' => 'Get', 'Post' => 'Post'];

        return view('webhook.edit', compact('webhook', 'module', 'method'));
    }

    public function update(Request $request, $id)
    {
        $webhook = Webhook::find($id);
      
            $validator = \Validator::make(
                $request->all(),
                [
                    'module' => 'required',
                    'method' => 'required',
                    'url' => 'required',
                ]
            );
            if ($validator->fails()) {
                $messages = $validator->getMessageBag();

                return redirect()->back()->with('error', $messages->first());
            }

            $webhook->module  = $request->module;
            $webhook->method  = $request->method;
            $webhook->url     = $request->url;
            $webhook->save();

            return redirect()->back()->with('success', __('Webhook Successfully updated.'));
        
    }
    public function destroy($id)
    {
        Webhook::where('id', $id)->delete();

        return redirect()->back()->with('success', 'Webhook Successfully deleted.');
    }
}
