<?php


function call_amazon_api($function_name='CreatePlatformEndpoint',$params=array('PlatformApplicationArn'=>'arn:aws:sns:us-west-2:012380123123:app/APNS/test_IOS_prod','Token'=>'12312312304DC75B8D6E8200DFF12356E8DAEACEC428B427E9518741C92C6123')) {

    $secretKey='123xOmyQW9v123/123lxDm123SzcW+d72EPZ3123';
    $AccessKeyId='123123QI6NXT5ZGU123';
    $region='us-west-2';

    $dt_str=gmdate('Ymd\THis\Z');
    $dt_str_short=date('Ymd');

    $signed_headers='content-type;host;x-amz-date';
    $http_body='Action='.$function_name.'&Version=2010-03-31&'.http_build_query($params);

    $canon =  "POST\n"
        . '/'. "\n"
        .''. "\n"
        .'content-type:application/x-www-form-urlencoded'."\n"
        .'host:sns.'.$region.'.amazonaws.com'."\n"
        .'x-amz-date:'.$dt_str."\n"
        ."\n".$signed_headers."\n".hash('sha256',$http_body);
    $string_to_sign = "AWS4-HMAC-SHA256\n".$dt_str."\n".$dt_str_short.'/'.$region.'/sns/aws4_request'."\n" . hash('sha256', $canon);

    $dateKey = hash_hmac('sha256', $dt_str_short, 'AWS4' . $secretKey, true);
    $regionKey = hash_hmac('sha256', $region, $dateKey, true);
    $serviceKey = hash_hmac('sha256', 'sns', $regionKey, true);
    $signingKey = hash_hmac('sha256', 'aws4_request', $serviceKey, true);
    $signature = hash_hmac('sha256', $string_to_sign, $signingKey);
    $Authorization_header='AWS4-HMAC-SHA256 '."Credential=".$AccessKeyId."/".$dt_str_short."/".$region."/sns/aws4_request, "."SignedHeaders=".$signed_headers." Signature=".$signature;

    $post_opts = array(
        'http'=>array(
            'ignore_errors'=>true,
            "timeout" => 10,
            'method'=>"POST",
            'header'=>
                "Content-Type: application/x-www-form-urlencoded\n".
                "x-amz-date: ".$dt_str."\n".
                "Authorization: ".$Authorization_header,
            'content'=>$http_body
        )
    );

    @$file_content = file_get_contents('http://sns.'.$region.'.amazonaws.com/', false, stream_context_create($post_opts) );

    $ret=false;
    if(strpos($file_content,'<ErrorResponse>')!==FALSE) {
        preg_match('#<Message>([^<>]+)#i',$file_content,$ret);
        if(isset($ret[1]) && !empty($ret[1])) {
            $ret=$ret[1];
        } else {
            $ret='error';
        }
        $ret=array('status'=>false,'data'=>$ret);
    } else {

        $ret = new SimpleXMLElement("<?xml version='1.0' standalone='yes'?>".$file_content);
        if(empty($ret)) {
            $ret=false;
        } else {
            $ret = json_encode($ret);
            $ret = json_decode($ret,TRUE);
        }
        if(isset($ret[$function_name.'Result'])) {
            $ret=array('status'=>true,'data'=>$ret[$function_name.'Result']);
        } else {
            $ret=array('status'=>false,'data'=>$ret);
        }

    }

    return $ret;
}
 
function send_token_to_amazon($newtoken,$targetArn='arn:aws:sns:us-west-2:012380123123:app/APNS/test_IOS_prod') {

    $ret=call_amazon_api('CreatePlatformEndpoint',array('PlatformApplicationArn'=>$targetArn, 'Token'=>$newtoken));


    if($ret && !empty($ret['status']) && isset($ret['data']['EndpointArn'])) {
 

        return $ret['data']['EndpointArn'];
    } else {
        return false;
    }


   
}


 /*
     * good content:
     *
    <CreatePlatformEndpointResponse xmlns="http://sns.amazonaws.com/doc/2010-03-31/">
      <CreatePlatformEndpointResult>
        <EndpointArn>arn:aws:sns:us-west-2:012380123123:endpoint/APNS/test_IOS_prod/8fb4f9e0-298a-38a3-9cf2-47614468d37e</EndpointArn>
      </CreatePlatformEndpointResult>
      <ResponseMetadata>
        <RequestId>204ac6f2-6f75-5e06-b060-889aa4a90435</RequestId>
      </ResponseMetadata>
    </CreatePlatformEndpointResponse>
     *
     * error content:
     *
    <ErrorResponse xmlns="http://sns.amazonaws.com/doc/2010-03-31/">
      <Error>
        <Type>Sender</Type>
        <Code>InvalidParameter</Code>
        <Message>Invalid parameter: Token Reason: iOS device tokens must be 64 hexadecimal characters</Message>
      </Error>
      <RequestId>002b07cb-a3c6-5012-9f15-49a368ace225</RequestId>
    </ErrorResponse>
     * */


?>
