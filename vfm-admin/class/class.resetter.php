<?php
/**
 * Control password reset
 *
 * @category PHP
 * @package  VenoFileManager
 * @author   Nicola Franchini <info@veno.it>
 * @license  Exclusively sold on CodeCanyon
 * @link     http://filemanager.veno.it/
 */
if (!class_exists('Resetter', false)) {
    /**
     * Class Resetter
     *
     * @category PHP
     * @package  VenoFileManager
     * @author   Nicola Franchini <info@veno.it>
     * @license  Exclusively sold on CodeCanyon
     * @link     http://filemanager.veno.it/
     */
    class Resetter
    {
        /**
         * Call update user functions
         *
         * @return $message
         */
        public function init()
        {
            global $updater;
            global $resetter;

            $resetpwd = filter_input(INPUT_POST, 'reset_pwd', FILTER_SANITIZE_SPECIAL_CHARS);
            $resetconf = filter_input(INPUT_POST, 'reset_conf', FILTER_SANITIZE_SPECIAL_CHARS);
            $userh = filter_input(INPUT_POST, 'userh', FILTER_SANITIZE_SPECIAL_CHARS);
            $getrp = filter_input(INPUT_POST, 'getrp', FILTER_SANITIZE_SPECIAL_CHARS);

            if ($resetpwd && $resetconf && $userh && $getrp) {
                if ($resetpwd == $resetconf && $resetter->checkTok($getrp, $userh) === true) {
                    $username = $resetter->getUserFromSha($userh);
                    if ($username) {
                        $new_users = $updater->updateUserPwd($username, $resetpwd, false);
                        $updater->updateUserFile('password', false, $new_users);
                        $resetter->resetToken($resetter->getMailFromSha($userh));
                    }
                }
            }
        }

        /**
         * Get user name from encrypted email
         *
         * @param string $usermailsha user email in SHA1
         *
         * @return username
         */
        public function getUserFromSha($usermailsha)
        {
            global $gateKeeper;
            $_USERS = $gateKeeper->getUsers();

            foreach ($_USERS as $value) {
                if (isset($value['email']) && sha1($value['email']) === $usermailsha) {
                    return $value['name'];
                }
            }
            return false;
        }

        /**
         * Get user mail from encrypted email
         *
         * @param string $usermailsha user email in SHA1
         *
         * @return username
         */
        public function getMailFromSha($usermailsha)
        {
            global $gateKeeper;
            $_USERS = $gateKeeper->getUsers();
            foreach ($_USERS as $value) {
                if (isset($value['email']) && sha1($value['email']) === $usermailsha) {
                    return $value['email'];
                }
            }
            return false;
        }

        /**
         * Get user name from email
         *
         * @param string $usermail user email
         *
         * @return username
         */
        public function getUserFromMail($usermail)
        {
            global $gateKeeper;
            $_USERS = $gateKeeper->getUsers();
            foreach ($_USERS as $value) {
                if (isset($value['email'])) {
                    if ($value['email'] === $usermail) {
                        return $value['name'];
                    }
                }
            }
            return false;
        }

        /**
         * Reset token
         *
         * @param string $usermail user email
         *
         * @return mail to user
         */
        public function resetToken($usermail)
        {
            $_TOKENS = $this->getTokens();
            $tokens = $_TOKENS;
            unset($tokens[$usermail]);
            $tkns = '$_TOKENS = ';
            if (false == (file_put_contents(dirname(__DIR__).'/_content/users/token.php', "<?php\n\n $tkns".var_export($tokens, true).";\n"))) {
                Utils::setError('error, no token reset');
                return false;
            }
        }

        /**
         * Set token for password recovering
         *
         * @param string $usermail user email
         *
         * @return mail to user
         */
        public function setToken($usermail)
        {
            global $resetter;
            global $setUp;

            $_TOKENS = $this->getTokens();
            $tokens = $_TOKENS;

            $birth = time();
            $salt = $setUp->getConfig('salt');
            $token = sha1($salt.$usermail.$birth);

            $tokens[$usermail]['token'] = $token;
            $tokens[$usermail]['birth'] = $birth;
            $tkns = '$_TOKENS = ';

            if (false == (file_put_contents(dirname(__DIR__).'/_content/users/token.php', "<?php\n\n $tkns".var_export($tokens, true).";\n"))) {
                return false;
            } else {
                $message = array();
                $message['name'] = $resetter->getUserFromMail($usermail);
                $message['tok'] = '?rp='.$token.'&usr='.sha1($usermail);
                return $message;
            }
            return false;
        }

        /**
         * Check token validity and lifetime
         *
         * @param string $getrp  time to check
         * @param string $getusr getusr to check
         *
         * @return true/false
         */
        public function checkTok($getrp, $getusr)
        {
            $_TOKENS = $this->getTokens();
            $now = time();

            foreach ($_TOKENS as $key => $value) {
                if (sha1($key) === $getusr) {
                    if ($value['token'] === $getrp) {
                        if ($now < $value['birth'] + 3600) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }


        /**
         * Get available tokens
         *
         * @return $_TOKENS
         */
        public function getTokens()
        {
            include dirname(__DIR__).'/_content/users/token.php';
            return $_TOKENS;
        }

        /**
         * Set token for password recovering
         *
         * @param object $mail     PHPMailer
         * @param string $usermail user email
         * @param string $username usename
         * @param bool   $is_new   new user or reminder
         *
         * @return mail to user
         */
        public function sendUserReminder($mail, $usermail, $username, $is_new = false)
        {
            global $setUp;

            $setfrom = $setUp->getConfig('email_from');

            if (strlen($setfrom) < 4) {
                exit;
            }

            $template = $is_new ? '/_content/mail-template/template-new-user.html' : '/_content/mail-template/template-reset-password.html';

            $mail->CharSet = 'UTF-8';
            $mail->setLanguage($setUp->lang);

            if ($setUp->getConfig('smtp_enable') == true) {
                $mail->isSMTP();
                $mail->SMTPDebug = ($setUp->getConfig('debug_smtp') ? 2 : 0);
                $mail->Debugoutput = 'html';
                $smtp_auth = $setUp->getConfig('smtp_auth');

                $mail->Host = $setUp->getConfig('smtp_server');
                $mail->Port = (int)$setUp->getConfig('port');

                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    )
                );

                if ($setUp->getConfig('secure_conn') !== "none") {
                    $mail->SMTPSecure = $setUp->getConfig('secure_conn');
                }

                $mail->SMTPAuth = $smtp_auth;

                if ($smtp_auth == true) {
                    $mail->Username = $setUp->getConfig('email_login');
                    $mail->Password = $setUp->getConfig('email_pass');
                }
            }

            $script_url = $setUp->getConfig('script_url');
            $mail->setFrom($setfrom, $setUp->getConfig('appname'));
            $mail->addAddress($usermail, '<'.$usermail.'>');

            // alt message
            if ($is_new) {
                $mail->Subject = $setUp->getConfig('appname').": ".$setUp->getString('new_user');
                $altmessage = $script_url."\r\n"
                .$setUp->getString('new_user_has_been_created')."\r\n"
                .$setUp->getString('username').": ".$username."\r\n";
            } else {
                $mail->Subject = $setUp->getConfig('appname').": ".$setUp->getString('login_details');
                $altmessage = $script_url."\r\n"
                .$setUp->getString('username').": ".$username."\r\n"
                .$setUp->getString('reset_password')."\r\n"
                .$script_url.$token['tok'];
            }

            // .$setUp->getString('password').":".$postnewuserpass;

            $email_logo = $setUp->getConfig('email_logo', false) ? '_content/uploads/'.$setUp->getConfig('email_logo') : 'images/px.png';

            $mail->AddEmbeddedImage($email_logo, 'logoimg');

            $token = $this->setToken($usermail);

            if (!$token) {
                Utils::setError('Error creating reset token');
                return false;
            }

            // Retrieve the email template required
            $message = file_get_contents(dirname(__DIR__).$template);

            // Replace the % with the actual information
            $message = str_replace('%app_url%', $script_url, $message);
            $message = str_replace('%app_name%', $setUp->getConfig('appname'), $message);
            $message = str_replace('%translate_username%', $setUp->getString('username'), $message);
            $message = str_replace('%username%', $username, $message);

            if ($is_new) {
                $message = str_replace('%translate_new_user_has_been_created%', $setUp->getString('new_user_has_been_created'), $message);
                $message = str_replace('%translate_password%', $setUp->getString('reset_password'), $message);
                $message = str_replace('%password%', $script_url.$token['tok'], $message);
            } else {
                $message = str_replace(
                    '%translate_someone_requested_pwd_reset_1%', 
                    $setUp->getString('someone_requested_pwd_reset_1'), $message
                );
                $message = str_replace(
                    '%translate_someone_requested_pwd_reset_2%', 
                    $setUp->getString('someone_requested_pwd_reset_2'), $message
                );
                $message = str_replace(
                    '%translate_someone_requested_pwd_reset_3%', 
                    $setUp->getString('someone_requested_pwd_reset_3'), $message
                );

                $message = str_replace('%translate_reset_password%', $setUp->getString('reset_password'), $message);
                $message = str_replace('%tok%', $script_url.$token['tok'], $message);
            }

            $mail->msgHTML($message);

            $mail->AltBody = $altmessage;

            if (!$mail->send()) {
                Utils::setError('<strong>Mailer Error:</strong> '.$mail->ErrorInfo);
            } else {
                Utils::setSuccess($setUp->getString("email_sent"));
            }

        }



    }
}
