<?php

namespace VinCSS\TwoFactor;

use VinCSS\TwoFactor\Model\Profile2FA;



class TwoFactor
{

    public function renderProfilePage()
    {
        $profile = new Profile2FA();
?>

        <div class="wrap" id="v-setup-2fa">
            <h3>
                <?php echo __('Two-Factor Authentication Setting', 'vincss-fido2-login') ?>
            </h3>

            <?php

            $user_id = getCurrentUserId();
            $user = get_user_by('ID', $user_id);

            $is_enable_2fa = get_user_meta($user_id, 'vincss_enable_2fa', true);
            if ($is_enable_2fa) {
                $profile->manager2FA();
            } else {
                $profile->setup2FA();
            }


            $require_2fa = $this->isRequireEnable2FA($user->roles);
            $action = sanitize_text_field($_GET['action']);

            if ((isset($action) && 'vincss-setup-2fa' ===  $action) && !$is_enable_2fa && $require_2fa) {
            ?>
                <script>
                    jQuery(document).ready(function() {
                        const modal_2fa = jQuery('#vincss-2fa-modal-setup');
                        const btn_close_modal = jQuery('.btn-close-modal-2fa');
                        btn_close_modal.css('display', 'none');
                        modal_2fa.modal({
                            backdrop: 'static',
                            keyboard: false,
                        });
                        modal_2fa.modal('show');

                    });
                </script>
            <?php }
            ?>
        </div>
    <?php
    }

    public static function getRoles()
    {
        global $wp_roles;
        $roles = $wp_roles->role_names;
        return $roles;
    }

    public static function isRequireEnable2FA($user_roles)
    {
        $enforce_2fa = getOption('enforce_2fa');
        switch ($enforce_2fa) {
            case 'all':
                return true;
            case 'roles':
                $role_enforce_2fa = getOption('role_enforce_2fa');
                $is_match_role = false;
                foreach ($user_roles as $key => $role) {
                    if (in_array($role, $role_enforce_2fa)) {
                        $is_match_role = true;
                    }
                }
                if (!$is_match_role) {
                    return false;
                }
                return true;
            default:
                return false;
        }
    }

    public function renderMenuPage()
    {
        $v_pwl_ref_settings = sanitize_text_field($_POST['v_pwl_ref_settings']);
        $enforce_2fa = sanitize_text_field($_POST['enforce_2fa']);

        if ((isset($v_pwl_ref_settings) && $v_pwl_ref_settings === 'true') && isset($enforce_2fa) && current_user_can('manage_options')) {
            updateOption('enforce_2fa', sanitize_text_field($enforce_2fa));
            updateOption('is_backup_code', sanitize_text_field($_POST['is_backup_code']));
            add_settings_error("vincss_2fa_settings", "save_success", "Settings saved.", 'success');
        } elseif ((isset($v_pwl_ref_settings) && $v_pwl_ref_settings === 'true')) {
            add_settings_error("vincss_2fa_settings", "save_error", "Settings NOT saved.");
        }
        $roles = sanitizeArray($_POST['role_enforce_2fa']);

        $roles = isset($roles) ? (array) $roles : array();
        $roles = array_map('sanitize_text_field', $roles);

        if (!empty($roles)) {
            updateOption('role_enforce_2fa', $roles);
        }

        $vincss_2fa_backup_code = getOption('is_backup_code');
    ?>


        <div class="vcss_notice">
            <?php settings_errors("vincss_2fa_settings"); ?>
        </div>
        <h1 class="v-title mt-3">
            <?php echo __('Two-Factor Authentication', 'vincss-fido2-login') ?>
        </h1>
        <div class="mt-2">
            <form method="post" action="">
                <?php
                wp_nonce_field('v_pwl_options_update');
                ?>
                <input type='hidden' name='v_pwl_ref_settings' value='true'>

                <table class="form-table form_2fa">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <?php _e('Enforce two-factor authentication:', 'vincss-fido2-login'); ?>
                            </th>
                            <td>
                                <?php
                                $enforce_2fa = getOption('enforce_2fa');
                                ?>

                                <select name="enforce_2fa" id="enforce_2fa" class="form-select w-100 ">
                                    <option value="all" <?php if ($enforce_2fa === 'all') { ?> selected <?php } ?>> <?php _e('All users', 'vincss-fido2-login'); ?>
                                    </option>
                                    <option value="roles" <?php if ($enforce_2fa === 'roles') { ?> selected <?php } ?>><?php _e('Only for specific roles', 'vincss-fido2-login'); ?>
                                    </option>
                                    <option value="none" <?php if ($enforce_2fa === 'none') { ?> selected <?php } ?>><?php _e('Do not enforce on any users', 'vincss-fido2-login'); ?>
                                    </option>
                                </select>
                                <div class="mt-2" id="role_enforce_2fa_list">
                                    <div class="d-flex align-items-cente flex-wrap">
                                        <?php
                                        $roles_selected_default = getOption('role_enforce_2fa');
                                        $roles = $this->getRoles();
                                        foreach ($roles as $value => $label) {
                                        ?>
                                            <div class="me-3">
                                                <fieldset>
                                                    <label class="v-checkbox" for="<?php echo esc_attr($value) ?>">
                                                        <div class="d-flex align-items-center">
                                                            <input class="v-checkbox-input" type="checkbox" id="<?php echo esc_attr($value) ?>" name="role_enforce_2fa[]" value="<?php echo esc_attr($value); ?>" <?php if (in_array($value, $roles_selected_default)) { ?>checked="checked" <?php } ?>>
                                                            <span class="v-checkbox-checkmark-box">
                                                                <span class="v-checkbox-checkmark"></span>
                                                            </span>
                                                            <span class="ms-1"> <?php echo esc_attr($label) ?>
                                                        </div>
                                                    </label>
                                                </fieldset>
                                            </div>

                                        <?php
                                        }
                                        ?>
                                    </div>

                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="pt-3">
                                <?php _e('Backup code:', 'vincss-fido2-login'); ?>
                            </th>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="is_backup_code" name="is_backup_code" value="true" <?php echo  $vincss_2fa_backup_code === 'true' ? 'checked' : '' ?>> <span>
                                        <i></i>
                                    </span>
                                </label>
                                <p class="description"><?php _e('Enable MFA backup verification codes.', 'vincss-fido2-login'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="user_config"><?php _e('Setup authenticator app', 'vincss-fido2-login'); ?></label></th>
                            <td>
                                <a href=" <?php echo admin_url('profile.php?#v-setup-2fa'); ?>" class="button button-primary"><?php _e('Add', 'vincss-fido2-login'); ?></a>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

<?php
    }
}
?>