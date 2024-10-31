
function validate_fields() {
    if (merchant.wc_merchant_id == jQuery('#merchant_id').val()) {
        //alert(merchant.id_conflict_message);
        jQuery('#merchant_id').focus();
        return false;
    }
    return true;
}


jQuery('#merchant_id').change(function () {
    if (!validate_fields()) {
        jQuery('#merchant_id').css("color", "red");
        jQuery('#merchant_id').after('<p id="merchant_conflict_msg" style="color: red">' + merchant.id_conflict_message + '</p>');
    } else {
        jQuery('#merchant_conflict_msg').remove();
        jQuery('#merchant_id').css("color", "");
    }
});

jQuery('#merchant_logo_tooltip').attr('title', merchant.merchant_logo_tooltip);
jQuery('#order_prefix_tooltip').attr('title', merchant.order_prefix_tooltip);
jQuery('#description_tooltip').attr('title', merchant.description_tooltip);
jQuery('#redirect_new_page_tooltip').attr('title', merchant.redirect_new_page_tooltip);
jQuery('#enable_debug_tooltip').attr('title', merchant.enable_debug_tooltip);
jQuery('#sandbox_mode_tooltip').attr('title', merchant.sandbox_mode_tooltip);
jQuery('#merchant_id_tooltip').attr('title', merchant.merchant_id_tooltip);
jQuery('#merchant_secret_tooltip').attr('title', merchant.merchant_secret_tooltip);
jQuery('#merchant_key_tooltip').attr('title', merchant.merchant_key_tooltip);
jQuery('#donate_ok_page_id_tooltip').attr('title', merchant.donate_ok_page_id_tooltip);
jQuery('#donate_nok_page_id_tooltip').attr('title', merchant.donate_nok_page_id_tooltip);
jQuery('#currency_tooltip').attr('title', merchant.currency_tooltip);
jQuery('#language_tooltip').attr('title', merchant.language_tooltip);