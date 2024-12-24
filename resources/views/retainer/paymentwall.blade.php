<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
@php
    $retainer = $data['retainer_id'];
    $retainer_id = \Illuminate\Support\Facades\Crypt::decrypt($retainer);
    $price = $data['amount'];

@endphp
{{-- {{ dd( $admin_payment_setting) }} --}}
<script src="https://api.paymentwall.com/brick/build/brick-default.1.5.0.min.js"> </script>
<div id="payment-form-container"> </div>
<script>
  var brick = new Brick({
    public_key: '{{ $company_payment_setting['paymentwall_public_key'] }}', // please update it to Brick live key before launch your project
    amount: '{{ $price }}',
    currency: '{{App\Models\Utility::getValByName('site_currency')}}',
    container: 'payment-form-container',
    action: '{{route("retainer.pay.with.paymentwall",[$data["retainer_id"],"amount" => $data["amount"]])}}',
    form: {
      merchant: 'Paymentwall',
      product: '{{$retainer_id}}',
      pay_button: 'Pay',
      show_zip: true, // show zip code
      show_cardholder: true // show card holder name
    }
});
brick.showPaymentForm(function(data) {
      if(data.flag == 1){
        console.log('dsfrserf');
        window.location.href ='{{route("error.retainer.show",[1, 'retainer_id'])}}'.replace('retainer_id',data.retainer);
      }else{
        console.log('22222');
        window.location.href ='{{route("error.retainer.show",[2, 'retainer_id'])}}'.replace('retainer_id',data.retainer);
      }
    }, function(errors) {
      if(errors.flag == 1){
        console.log('xcfdr');
        window.location.href ='{{route("error.retainer.show",[1,'retainer_id'])}}'.replace('retainer_id',errors.retainer);
      }else{
        console.log('11111');
        window.location.href ='{{route("error.retainer.show",[2, 'retainer_id'])}}'.replace('retainer_id',errors.retainer);
      }

    });

</script>
