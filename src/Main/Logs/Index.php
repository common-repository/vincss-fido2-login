<div class="w-100">
    <h1 class="v-title"><?php _e('Logs', 'vincss-fido2-login'); ?></h1>
    <div class="mt-2">
        <div class="d-flex justify-content-end mb-3">
            <button id="clear_log" class="button button-primary my-0"><?php _e('Clear log', 'vincss-fido2-login'); ?></button>
        </div>


        <textarea name="vincss_fido2_login_log" id="vincss_fido2_login_log" rows="20" class="w-100" readonly><?php echo implode("\n", get_option("vincss_fido2_login_log")); ?></textarea>
        <p class="description"><?php _e('Automatic update every 5 seconds.', 'vincss-fido2-login'); ?></p>
    </div>
</div>