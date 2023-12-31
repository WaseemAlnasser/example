<?php

namespace App\Http\Controllers;

use App\Models\Response;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Welcome to the API',
            'data' => null
        ], 200);
    }

    /**
     * @throws GuzzleException
     */
    public function transaction(Request $request)
    {

        $email = $request->input('email');
        $key = $request->input('key');
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }else{
            $user->transaction = $key;
            $user->save();
           // make a jwt token to send to apple api
            $header = [
                'alg' => 'ES256',
                'kid' =>  'AH843Y58W8',
                'typ' => 'JWT'
            ];
            $payload = [
                'iss' => '00760ed9-d423-49c4-b9b6-425819682393',
                'iat' => time(),
                // for exp add 40 minutes to current time
                'exp' => time() + 3000,
                'aud' => 'appstoreconnect-v1',
                'bid' => 'com.firebirdvpn',
            ];
            $key = file_get_contents(storage_path('app/AuthKey_AH843Y58W8.p8'));
            $jwt = JWT::encode($payload, $key, 'ES256', 'AH843Y58W8');
            // make http request to apple api
            $http = new \GuzzleHttp\Client;
            $url = 'https://api.storekit-sandbox.itunes.apple.com/inApps/v1/subscriptions/'.$user->transaction;
            $resp = $http->get($url, [
               'headers' => [
                   'Authorization' => 'Bearer '.$jwt,
                   'Content-Type' => 'application/json'
               ]
            ]);
            $data2 = $resp->getBody()->getContents();
            $payload = json_decode($data2);
            $data = $payload->data;
            $signedTransactionInfo = $data[0]->lastTransactions[0]->signedTransactionInfo;
            $signedTransactionInfoArr = explode('.', $signedTransactionInfo);
            $signedTransactionInfo = base64_decode($signedTransactionInfoArr[1]);
            $signedTransactionInfo = json_decode($signedTransactionInfo);
//            $signedRenewalInfo = $payload->data->signedRenewalInfo;
//            $signedRenewalInfoArr = explode('.', $signedRenewalInfo);
//            $signedRenewalInfo = base64_decode($signedRenewalInfoArr[1]);
//            $signedRenewalInfo = json_decode($signedRenewalInfo);
            $key = $data[0]->lastTransactions[0]->originalTransactionId;
            $array = [
                //status
                //1
                //The auto-renewable subscription is active.
                //2
                //The auto-renewable subscription is expired.
                //3
                //The auto-renewable subscription is in a billing retry period.
                //4
                //The auto-renewable subscription is in a Billing Grace Period.
                //5
                //The auto-renewable subscription is revoked.
                'status' => $data[0]->lastTransactions[0]->status,
                'originalTransactionId' => $key,//originalTransactionId the main identifier that is stored in the users table
                'product_id' => $signedTransactionInfo->productId,//the plan id in the app store
                'isUpgraded' => $signedTransactionInfo->isUpgraded ?? '',//true if the user upgraded from a lower plan to a higher plan
                'current_purchaseDate' => $signedTransactionInfo->purchaseDate,//the date of the current purchase (unix timestamp)
                'originalPurchaseDate' => $signedTransactionInfo->originalPurchaseDate,//the date of the original purchase (unix timestamp)
                'expiresDate' => $signedTransactionInfo->expiresDate,//the date of the expiration (unix timestamp)
                'storefront' => $signedTransactionInfo->storefront,//the country of the app store for this purchase
                'transactionReason' => $signedTransactionInfo->transactionReason,//the reason for the transaction which indicates whether it’s a customer’s purchase or a renewal for an auto-renewable subscription that the system initiates
                'type' => $signedTransactionInfo->type,//the type of the transaction which indicates whether it’s a customer’s purchase or a renewal for an auto-renewable subscription that the system initiates
//                'autoRenewProductId' => $signedRenewalInfo->autoRenewProductId,//The product identifier of the product that renews at the next billing period (happens if the user downgrades his plan)
//                'autoRenewStatus' => $signedRenewalInfo->autoRenewStatus,//the auto-renew status of the subscription 0 = off, 1 = on
//                'expirationIntent' => $signedRenewalInfo->expirationIntent ?? '',//the reason for the subscription expiration
            ];
            return response()->json([
                'success' => true,
                'message' => 'User found',
                'data' =>$array
            ], 200);
        }

    }

    public function handle2(Request $request)
    {
       $body = $request->getContent();
         $response = new Response();
        $response->content = $body;
        $response->save();
        http_response_code(200);
    }

    public function handle(Request $request)
    {


        try
        {
            $array = [];
            $jws = $request->input('signedPayload');
            $jwsArr = explode('.', $jws);
            $header = base64_decode($jwsArr[0]);
            $payload = base64_decode($jwsArr[1]);
            $signature = base64_decode($jwsArr[2]);
            $payload = json_decode($payload);
            $data = $payload->data;
            $signedTransactionInfo = $payload->data->signedTransactionInfo;
            $signedTransactionInfoArr = explode('.', $signedTransactionInfo);
            $signedTransactionInfo = base64_decode($signedTransactionInfoArr[1]);
            $signedTransactionInfo = json_decode($signedTransactionInfo);
            $signedRenewalInfo = $payload->data->signedRenewalInfo;
            $signedRenewalInfoArr = explode('.', $signedRenewalInfo);
            $signedRenewalInfo = base64_decode($signedRenewalInfoArr[1]);
            $signedRenewalInfo = json_decode($signedRenewalInfo);
            $key = $signedTransactionInfo->originalTransactionId;

            $array = [
                //status
                //1
                //The auto-renewable subscription is active.
                //2
                //The auto-renewable subscription is expired.
                //3
                //The auto-renewable subscription is in a billing retry period.
                //4
                //The auto-renewable subscription is in a Billing Grace Period.
                //5
                //The auto-renewable subscription is revoked.
                'status' => $data->status,
                'originalTransactionId' => $key,//originalTransactionId the main identifier that is stored in the users table
                'product_id' => $signedTransactionInfo->productId,//the plan id in the app store
                'isUpgraded' => $signedTransactionInfo->isUpgraded ?? '',//true if the user upgraded from a lower plan to a higher plan
                'current_purchaseDate' => $signedTransactionInfo->purchaseDate,//the date of the current purchase (unix timestamp)
                'originalPurchaseDate' => $signedTransactionInfo->originalPurchaseDate,//the date of the original purchase (unix timestamp)
                'expiresDate' => $signedTransactionInfo->expiresDate,//the date of the expiration (unix timestamp)
                'storefront' => $signedTransactionInfo->storefront,//the country of the app store for this purchase
                'transactionReason' => $signedTransactionInfo->transactionReason,//the reason for the transaction which indicates whether it’s a customer’s purchase or a renewal for an auto-renewable subscription that the system initiates
                'type' => $signedTransactionInfo->type,//the type of the transaction which indicates whether it’s a customer’s purchase or a renewal for an auto-renewable subscription that the system initiates
                'autoRenewProductId' => $signedRenewalInfo->autoRenewProductId,//The product identifier of the product that renews at the next billing period (happens if the user downgrades his plan)
                'autoRenewStatus' => $signedRenewalInfo->autoRenewStatus,//the auto-renew status of the subscription 0 = off, 1 = on
                'expirationIntent' => $signedRenewalInfo->expirationIntent ?? '',//the reason for the subscription expiration
            ];
            $user = User::where('transaction', $key)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }
            $user->subscribed = 'true';
            $user->save();

            $encoded = json_encode($array);
            $response = new Response();
            $response->content = $encoded;
            $response->save();

        }catch (\Exception $e)
        {
            $response = new Response();
            $response->content = $e->getMessage();
            $response->save();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
        http_response_code(200);

    }

    public function getEmailContent()
    {
        return 'Return-Path: <wasemo.alnasser@gmail.com>
        Received: from mail-pj1-f45.google.com (mail-pj1-f45.google.com [209.85.216.45])
         by inbound-smtp.us-east-1.amazonaws.com with SMTP id djlgftrn8r6pefmamnda2pjrts7bm0b07bqmem81
         for info@wonderlox.com;
         Sat, 08 Jul 2023 13:21:39 +0000 (UTC)
        X-SES-Spam-Verdict: PASS
        X-SES-Virus-Verdict: PASS
        Received-SPF: pass (spfCheck: domain of _spf.google.com designates 209.85.216.45 as permitted sender) client-ip=209.85.216.45; envelope-from=wasemo.alnasser@gmail.com; helo=mail-pj1-f45.google.com;
        Authentication-Results: amazonses.com;
         spf=pass (spfCheck: domain of _spf.google.com designates 209.85.216.45 as permitted sender) client-ip=209.85.216.45; envelope-from=wasemo.alnasser@gmail.com; helo=mail-pj1-f45.google.com;
         dkim=pass header.i=@gmail.com;
         dmarc=pass header.from=gmail.com;
        X-SES-RECEIPT: AEFBQUFBQUFBQUFGYzdOb2srRzRSWmdDZGYrdUp5d0dUcHRGckU4SUcxOTlOTWtpNlNkcjhBdGlxTVdJUys2RUFnQ05uQ09nZ2lyeUdDdGZuellJQ0lZSlIvTmd2RXhWbktoVGlab2d4R0pJejZIUXBBSlJmZmhzYkRzSzYrcnFrdlpGRUZJWXdzWGl0Qm1Zc09TSzJ0ZDZRaFhSV1ZKM2k4THdNb0kvSmJibTBLSTBrQUd1cndRUStYMDBtWTNxNkZxaVhIQlN1YzZDbUVwdUhnQnZqZFpVZ2xudXU4ZkVERlBIa1A4UXpSV2RFWTNHOXcvZDVaS0xsdEZvTU5nenNZRE0vd3R0TitWNnNWQUJFV3Mwa240TE16cnFFZjlBcWN0eXN5VWV4TG02S3pXVEVXRGI1ZWc9PQ==
        X-SES-DKIM-SIGNATURE: a=rsa-sha256; q=dns/txt; b=Gqieac/fPS7KnS0O2p5+QBLTqtq/7Fjy7k8Ik6wWDH3GO5UitximV4jbq3iTzrQ8hUyUy172AlIf8FtBdLhiYkXexdUD3GGjQ0xulN4bmfAI+9dKCLVtaxCzIsP2pmSTIH5xF0LMr0IFPI/dYe2o9DHJExZgmBISEZcaJvLuV+s=; c=relaxed/simple; s=224i4yxa5dv7c2xz3womw6peuasteono; d=amazonses.com; t=1688822500; v=1; bh=A/Z6U+4sPvg6lrxmHuZhze0W3nHhLgrr4IBlK7ApQv8=; h=From:To:Cc:Bcc:Subject:Date:Message-ID:MIME-Version:Content-Type:X-SES-RECEIPT;
        Received: by mail-pj1-f45.google.com with SMTP id 98e67ed59e1d1-262ef07be72so1357339a91.1
                for <info@wonderlox.com>; Sat, 08 Jul 2023 06:21:39 -0700 (PDT)
        DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
                d=gmail.com; s=20221208; t=1688822498; x=1691414498;
                h=to:subject:message-id:date:from:mime-version:from:to:cc:subject
                 :date:message-id:reply-to;
                bh=A/Z6U+4sPvg6lrxmHuZhze0W3nHhLgrr4IBlK7ApQv8=;
                b=lcgqNXDwoAyMR1A3ITxWVuIJYV72bUcDshFwR6GcopnBm0RI9FDjY/74E+MpV4EXx9
                 65p2dt2NaFKy2oP9xNw/ewCZAYr6lr3zVNNtw2zfvqOCEOvS3actMYpAlg9zczv/gRKq
                 GDFrxP59bIjmJSldRb1EKuX22jAZBAkDwgTnOIFCfghxAwAAa//YgjizpPBU+f/PCksw
                 v35mVZk36gI8k1qFfvgB7c/NGqmR3Bh+wBW1BgbSzkGEp48RSXI9J4R+vZes05kZ4CP7
                 rcxtvqamy0db3vfSI9HRUUpWXGIbeVBAhZT+WPZfasAqkfYc9Mby9fX9bhRYlIEgj1L+
                 XZ8A==
        X-Google-DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed;
                d=1e100.net; s=20221208; t=1688822498; x=1691414498;
                h=to:subject:message-id:date:from:mime-version:x-gm-message-state
                 :from:to:cc:subject:date:message-id:reply-to;
                bh=A/Z6U+4sPvg6lrxmHuZhze0W3nHhLgrr4IBlK7ApQv8=;
                b=RpiRBsNS6+AB2MaeF4a414zhpDZCJIBJRImb0bs27RyGhQZydMIdujQGWSCfk0ZGEr
                 k90XRuHkqJvWGDhF7uno7j3LpCn155/t4xjV78NDLZbpygi3rn0zprjeC28JzTQGkgNJ
                 6ePd1NZYdXsqfxr4bpkPisRwDnMOdOKHaI9RUyUWxF1V5RAf4Cw7Rz140fciIBgwgGq7
                 THGUxq8eFvU5mYKtjaXwwzA5dB16B2drsTb9SKVGbygUiYWHhV94xAuoBuDnmdqshn07
                 YC4BtFlhrelO9o0oBR95/p0iJV7SIrXmpxzwiZUXIsw3dXGzqE3t9KdPiu96lWYKZgK0
                 231g==
        X-Gm-Message-State: ABy/qLYyYpVksk1N+IdD8DhNxKWPUiN/2/tm9PrQCs6mZpTDwTt/AVMY
            xe/7sdw7w4gAnsRdA1jPAKBKptC8nTE5hz7K2hoo0klU
        X-Google-Smtp-Source: APBJJlEapVP3yLHpKryAJgqBY+7DxEkfVjosaxExMOTIjZUCK06cMoz4J+3RDWLYB4T0G8UVEoQbPJIz0uaI4H65608=
        X-Received: by 2002:a17:90a:6505:b0:262:e49b:12d0 with SMTP id
         i5-20020a17090a650500b00262e49b12d0mr4850229pjj.48.1688822498299; Sat, 08 Jul
         2023 06:21:38 -0700 (PDT)
        MIME-Version: 1.0
        From: Wasem Alnasser <wasemo.alnasser@gmail.com>
        Date: Sat, 8 Jul 2023 17:21:26 +0400
        Message-ID: <CAMT9brrj1XiJ8dsTY7dXvQQTBGJADwGy-3Q7_49AW8yigv2G5Q@mail.gmail.com>
        Subject: Domain change
        To: info@wonderlox.com
        Content-Type: multipart/alternative; boundary="00000000000019931705fff9a16f"

        --00000000000019931705fff9a16f
        Content-Type: text/plain; charset="UTF-8"

        Hello wonderlox
        I wish to change my domain on my from waseem.wonderlox.com to
        waseemalnasser.online
        Thank you

        --00000000000019931705fff9a16f
        Content-Type: text/html; charset="UTF-8"
        Content-Transfer-Encoding: quoted-printable

        Hello wonderlox=C2=A0<div dir=3D"auto">I wish to change my domain on my fro=
        m <a href=3D"http://waseem.wonderlox.com">waseem.wonderlox.com</a> to wasee=
        malnasser.online=C2=A0</div><div dir=3D"auto">Thank you=C2=A0</div>

        --00000000000019931705fff9a16f--
';
    }

    public function ses()
    {

        return view('ses');
    }
}
