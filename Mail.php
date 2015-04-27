<?php namespace zblues\framework;

class Mail extends Singleton
{
    private $_config; // serverName, skinPath
    
    public function setConfig($config)
    {
        $this->_config = $config;
    }
    
    private function _mailEncode($param)
    {
        return "=?UTF-8?B?".base64_encode($param)."?=";
    }

    public function getUserString($user = array())
    {
        if(empty($user) || empty($user['email'])) return '';

        return  $this->_mailEncode($user['name']) . "<" . $user['email'] . ">";
    }


    // 시스템이 자동으로 보내는 메일 : 가입, 탈퇴, 주문 등
    public function systemMail($toUser=array(), $title, $contents, $attachments=array())
    {
        $toArr = array();
        foreach($toUser as $i => $user) {
            $toArr[$i]['To'] = $this->getUserString($user);
        }

        $mailHeaders['From'] = $this->getUserString(array('name'=>'아르떼이', 'email'=>'methodsoft@arttei.com'));

        for($i=0; $i<count($toArr); $i++) {
            $ret = $this->mail("아르떼이", $toArr[$i]['To'], $title, $contents, $mailHeaders, $attachments);
        }
        return true;
    }

    public function rawMail($senderName, $senderMail, $receiverName, $receiverMail, $title, $contents, $attachments = array(), $extraParams = '', $skin='default')
    {
        $mailHeaders['From'] = $this->getUserString(array('name'=>$senderName, 'email'=>$senderMail));
        $to = $this->getUserString(array('name'=>$receiverName, 'email'=>$receiverMail));
        
        return $this->mail($senderName, $to, $title, $contents, $mailHeaders, $attachments, $extraParams, $skin);
    }

    public function mail($senderName, $to,  $subject, $message, $headers = array(), $attachments = array(), $extraParams = '', $skin='default')
    {
        $config = $this->reg->get('config');

        $enc_subject =  $this->_mailEncode($subject);

        // Define the boundray we're going to use to separate our data with.
        $mimeBoundary = '==mimeBoundary_' . md5(time());

        // Define attachment-specific headers
        $headers['MIME-Version'] = '1.0';
        $headers['Content-Type'] = 'multipart/mixed; boundary="' . $mimeBoundary . '"';

        // Convert the array of header data into a single string.
        $headersString = '';
        foreach($headers as $header_name => $header_value) {
            if(!empty($headersString)) {
                $headersString .= "\r\n";
            }
            $headersString .= $header_name . ': ' . $header_value;
        }

        // Message Body
        $messageString  = '--' . $mimeBoundary;
        $messageString .= "\r\n";
        //$messageString .= 'Content-Type: text/plain; charset="iso-8859-1"';
        $messageString .= 'Content-Type: text/html; charset="UTF-8"';
        $messageString .= "\r\n";
        //$messageString .= 'Content-Transfer-Encoding: 7bit';
        $messageString .= 'Content-Transfer-Encoding: 8bit';
        $messageString .= "\r\n";
        $messageString .= "\r\n";

        $skinContents = file_get_contents("{$this->_config['skinPath']}/{$skin}/mail.html");
        $skinContents = str_replace(array('{SERVER_NAME}', '{COMPANY_NAME}', '{SKIN}'), array($this->_config['serverName'], $senderName, $skin), $skinContents);
        $messageString .= sprintf($skinContents, $subject, $subject, $message);

        $messageString .= "\r\n";
        $messageString .= "\r\n";

        // Add attachments to message body
        foreach($attachments as $localFilename => $attachment_filename) {
            $encFilename = $this->_mailEncode($attachment_filename);
            if(is_file($localFilename)) {
                #echo "$localFilename 파일이 존재함";
                $messageString .= '--' . $mimeBoundary;
                $messageString .= "\r\n";
                $messageString .= 'Content-Type: application/octet-stream; name="' . $encFilename . '"';
                $messageString .= "\r\n";
                $messageString .= 'Content-Description: ' . $encFilename;
                $messageString .= "\r\n";

                $fp = @fopen($localFilename, 'rb'); // Create pointer to file
                $fileSize = filesize($localFilename); // Read size of file
                $data = @fread($fp, $fileSize); // Read file contents
                $data = chunk_split(base64_encode($data)); // Encode file contents for plain text sending

                $messageString .= 'Content-Disposition: attachment; filename="' . $encFilename . '"; size=' . $fileSize.  ';';
                $messageString .= "\r\n";
                $messageString .= 'Content-Transfer-Encoding: base64';
                $messageString .= "\r\n\r\n";
                $messageString .= $data;
                $messageString .= "\r\n\r\n";
            } else {
                #echo "$localFilename 파일없음";
            }
        }

        // Signal end of message
        $messageString .= '--' . $mimeBoundary . '--';
        #Util::msLog('['. __METHOD__ .'] ' . $messageString);
        // Send the e-mail.
        return mail($to, $enc_subject, $messageString, $headersString, $extraParams);
    }
}