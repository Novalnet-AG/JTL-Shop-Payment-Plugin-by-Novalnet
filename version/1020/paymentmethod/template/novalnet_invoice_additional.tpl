<fieldset>
    {if $error}
        <div class="alert alert-danger">{$error_desc}</div>
    {/if}
</fieldset>
<fieldset>
    <legend>{$payment_name}</legend>

    <input id="nn_payment" name="payment" type="hidden" value="novalnet_invoice" />
    <div class="alert alert-info">
        {$nn_lang.invoice_description}
            {if $test_mode}
                {$nn_lang.testmode}
            {/if}
    </div>
    {if $pin_error}
     <div class="row">
        <div class="col-xs-12 col-md-6">
            <div class="form-group float-label-control">
                <label class="control-label">{$nn_lang.callback_pin}</label>
                <input class="form-control" type="text" name="nn_pin" id="nn_pin" autocomplete="off" />
            </div>
                <input type="hidden" id="nn_pin_error_message" value="{$nn_lang.callback_pin_error}">
                <input type="hidden" id="nn_pin_empty_error_message" value="{$nn_lang.callback_pin_error_empty}">
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-md-6">
            <div>
                <span>
                    <input type="checkbox" name="nn_forgot_pin" id="nn_forgot_pin" />   {$nn_lang.callback_forgot_pin}
                </span>
            </div>
        </div>
    </div>

    {else}
        {if $pin_by_callback}
        <div class="row">
             <div class="col-xs-12 col-md-6">
                 <div class="form-group float-label-control inv_tel required">
                    <label class="control-label">{$nn_lang.callback_phone_number}</label>
                    <input class="form-control" type="text" name="nn_telnumber" id="nn_telnumber" size="32">
                 </div>
                 <input type="hidden" id="nn_tele_error_message" value="{$nn_lang.callback_telephone_error}">
             </div>
        </div>

        {elseif $pin_by_sms}
         <div class="row">
             <div class="col-xs-12 col-md-6">
                <div class="form-group float-label-control inv_sms required">
                    <label class="control-label">{$nn_lang.callback_sms}</label>
                    <input class="form-control" type="text" name="nn_mob_number" id="nn_mob_number" size="32">
                </div>
                <input type="hidden" id="nn_mob_error_message" value="{$nn_lang.callback_mobile_error}">
            </div>
          </div>
        {/if}
    {/if}
</fieldset>

{literal}
    <script type="text/javascript">
        $(document).ready(function(){
            var formid = $('#nn_payment').closest('form').attr('id');

                $('#'+formid).submit(function (evt) {
                    nn_pin_validate(evt);
                });
            });
            function nn_pin_validate(evt){
                if(document.getElementById('nn_telnumber')){
                    nn_tel_number = document.getElementById('nn_telnumber').value.replace(/^\s+|\s+$/g, '');}
                if(document.getElementById('nn_mob_number')){
                    nn_mob_number = document.getElementById('nn_mob_number').value.replace(/^\s+|\s+$/g, '');}
                if(document.getElementById('nn_pin')){
                    nn_pin  = document.getElementById('nn_pin').value.replace(/^\s+|\s+$/g, '');}

                if(typeof nn_tel_number != 'undefined' && (nn_tel_number == '' || isNaN(nn_tel_number))){
                    alert(jQuery('#nn_tele_error_message').val());
                    evt.preventDefault();
                }
                else if(typeof nn_mob_number != 'undefined' && (nn_mob_number == '' || isNaN(nn_mob_number))){
                    alert(jQuery('#nn_mob_error_message').val());
                    evt.preventDefault();
                }
                else if(typeof nn_pin != 'undefined'){
                    if(nn_pin == '' && !(document.getElementById('nn_forgot_pin').checked)){
                        alert(jQuery('#nn_pin_empty_error_message').val());
                        evt.preventDefault();
                    }
                    else if(validateSpecialChars(nn_pin) && !(document.getElementById('nn_forgot_pin').checked)){
                        alert(jQuery('#nn_pin_error_message').val());
                        document.getElementById('nn_pin').value = '';
                        evt.preventDefault();
                    }
                }
            }

            function validateSpecialChars(input_val)
            {
                var pattern = /^\s+|\s+$|([\/\\#,+@!^()$~%.":*?<>{}])/g;
                return pattern.test(input_val);
            }
    </script>
{/literal}
