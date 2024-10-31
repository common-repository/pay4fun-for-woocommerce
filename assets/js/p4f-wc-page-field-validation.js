jQuery(document).ready(function () {
    function validate_fields() {
        //console.log(merchant.donation_merchant_id);
        //console.log(jQuery('#woocommerce_pay4fun_merchant_id').val());
        if (merchant.donation_merchant_id == jQuery('#woocommerce_pay4fun_merchant_id').val()) {
            //alert(merchant.id_conflict_message);
            jQuery('#woocommerce_pay4fun_merchant_id').focus();
            return false;
        }
        return true;
    }

    jQuery('#mainform').submit(function () {
        return validate_fields();
    });

    jQuery('#woocommerce_pay4fun_title').prop("readonly", true);


    jQuery('#woocommerce_pay4fun_merchant_id').change(function () {
        if (!validate_fields()) {
            jQuery('#woocommerce_pay4fun_merchant_id').css("color", "red");
            jQuery('#woocommerce_pay4fun_merchant_id').after('<p id="merchant_conflict_msg" style="color: red">' + merchant.id_conflict_message + '</p>');
        } else {
            jQuery('#merchant_conflict_msg').remove();
            jQuery('#woocommerce_pay4fun_merchant_id').css("color", "");
        }
    });
});