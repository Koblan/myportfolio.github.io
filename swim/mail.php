<?php ini_set('display_errors', 'On');

$emails = array(
    /**
     * 'идентификатор страницы' => 'адрес для отправки' (можно несколько через запятую)
     */
    'default' => 'info@swim-kids.com'
    // default - Адрес по умолчанию.
    // Обязательный элемент.
    // Если форма была отправленна со страницы не указанной в списке,
    // то письмо отправляется на адрес по умолчанию
);
$subject = 'Заявка с сайта swim-kids.com'; // тема письма с указанием адреса сайта
$message = 'Данные формы:'; // вводная часть письма
$addreply = ''; // адрес куда отвечать (необязательно)
$from = 'Swim-kids.com Post server'; // имя отправителя (необязательно)
$smtp = 1; // отправлять ли через почтовый ящик, 1 - да, 0 - нет, отправлять через хостинг

// настройки почтового сервера для режима $smtp = 1 (Внимание: с GMAIL не работает) Для надежности лучше создать свой почтовый ящик и вписать сюда его данные.
$host = 'smtp.yandex.ru'; // сервер отправки писем
$username = 'noreply@swim-kids.com'; // логин вашего почтового ящика
$password = 'poskvs$3gre'; // пароль вашего почтового ящика
$auth = 1; // нужна ли авторизация, 1 - нужна, 0 - не нужна
$secure = 'ssl'; // тип защиты
$port = 465; // порт сервера
$charset = 'utf-8'; // кодировка письма

// дополнительные настройки
$cc = ''; // копия письма
$bcc = ''; // скрытая копия

$client_email = ''; // поле откуда брать адрес клиента
$client_message = ''; // текст письма, которое будет отправлено клиенту
$client_file = ''; // вложение, которое будет отправлено клиенту

$export_file = ''; // имя файла для экспорта в CSV
$export_fields = ''; // список полей для экспорта (через запятую)

$fields = "";

if (isset($_POST['page_name'])
    && isset($emails[$_POST['page_name']])
) {
    $send_to_email = $emails[$_POST['page_name']];

} else {
    $send_to_email = $emails['default'];
}

foreach ($_POST as $key => $value) {
    if ($value === 'on') {
        $value = 'Да';
    }
    if ($key === 'sendto') {
//        $email = $value;
    } elseif ($key === 'required_fields') {
        $required = explode(',', $value);
    } elseif ($key != 'page_name') {
        if (in_array($key, $required) && $value === '') {
            echo 'ERROR_REQUIRED';
            die();
        }
        if (is_array($value)) {
            $fields .= str_replace('_', ' ', $key) . ': <b>' . implode(', ', $value) . '</b> <br />';
        } else {
            if ($value !== '') {
                $fields .= str_replace('_', ' ', $key) . ': <b>' . $value . '</b> <br />';
            }
        }
    }
}

if ($export_file !== '') {
    $vars = explode(',', $export_fields);
    $str_arr[] = '"' . date("d.m.y H:i:s") . '"';
    foreach ($vars as $var_name) {
        if (isset($_POST[$var_name])) {
            $str_arr[] = '"' . $_POST[$var_name] . '"';
        }
    }
    file_put_contents($export_file, implode(';', $str_arr) . "\n", FILE_APPEND | LOCK_EX);
}

$post_data = get_post_data($_POST);

smtpmail($send_to_email, $subject, $message . '<br>' . $fields);
if ($client_email !== '') {
    $client_message === '' ? $message .= '<br>' . $fields : $message = $client_message;
    smtpmail($_POST[$client_email], $subject, $message, true);
}

function smtpmail($to, $subject, $content, $client_mode = false)
{

    global $success, $smtp, $host, $auth, $secure, $port, $username, $password, $from, $addreply, $charset, $cc, $bcc, $client_email, $client_message, $client_file;

    require_once('./class-phpmailer.php');
    $mail = new PHPMailer(true);
    if ($smtp) {
        $mail->IsSMTP();
    }
    try {
        $mail->SMTPDebug = 0;
        $mail->Host = $host;
        $mail->SMTPAuth = $auth;
        $mail->SMTPSecure = $secure;
        $mail->Port = $port;
        $mail->CharSet = $charset;
        $mail->Username = $username;
        $mail->Password = $password;

        if ($username !== '') $mail->SetFrom($username, $from);
        if ($addreply !== '') $mail->AddReplyTo($addreply, $from);

        $to_array = explode(',', $to);
        foreach ($to_array as $to) {
            $mail->AddAddress($to);
        }
        if ($cc !== '') {
            $to_array = explode(',', $cc);
            foreach ($to_array as $to) {
                $mail->AddCC($to);
            }
        }
        if ($bcc !== '') {
            $to_array = explode(',', $bcc);
            foreach ($to_array as $to) {
                $mail->AddBCC($to);
            }
        }

        $mail->Subject = htmlspecialchars($subject);
        $mail->MsgHTML($content);

        if ($client_file !== '' && $client_mode) {
            $mail->AddAttachment($client_file);
        } elseif (!$client_mode) {
            if ($_FILES['file']['name'][0] !== '') {
                $files_array = reArrayFiles($_FILES['file']);
                if ($files_array !== false) {
                    foreach ($files_array as $file) {
                        $mail->AddAttachment($file['tmp_name'], $file['name']);
                    }
                }
            }
        }

        $mail->Send();
        if (!$client_mode) echo('success');

    } catch (phpmailerException $e) {
        echo $e->errorMessage();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

function reArrayFiles(&$file_post)
{
    if ($file_post === null) {
        return false;
    }
    $files_array = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post);
    for ($i = 0; $i < $file_count; $i++) {
        foreach ($file_keys as $key) {
            $files_array[$i][$key] = $file_post[$key][$i];
        }
    }
    return $files_array;
}

 function get_post_data($array) {

    $phone_fields = array(
        'hone',
    );

    $email_fields = array(
        'mail',
    );

    $name_fields = array(
        'ame',
    );
        
    $service_fields = array(
        'nisender',
    );

    $email = get_value_by_one_of($array, $email_fields);
    $phone = get_value_by_one_of($array, $phone_fields);
    $name = get_value_by_one_of($array, $name_fields);
    $service = get_value_by_one_of($array, $service_fields);

    return array(
        'email' => $email,
        'phone' => $phone,
        'name' => $name,
        'service' => $service
    );
}

function get_value_by_one_of($array, $keys, $default = null) {
    foreach($array as $key => $value) {
        if (strripos($key, $keys[0])) {
             return $value;
        }
    }

    return $default;
}