<?php

namespace App\Http\Controllers;

use App\Models\Response;
use App\Models\User;
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

    public function transaction(Request $request)
    {
        $body = $request->getContent();
        $email = $body['email'];
        $key = $body['key'];
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }else{
            $user->transaction = $key;
            $user->save();
            return response()->json([
                'success' => true,
                'message' => 'Transaction key saved',
            ], 200);
        }

    }

    public function handle(Request $request)
    {
        // get the body of the request
        try
        {
            $jws = $request->input('signedPayload');
            $jwsArr = explode('.', $jws);
            $payload = base64_decode($jwsArr[1]);
            $payload = json_decode($payload);
            $signedTransactionInfo = $payload->signedTransactionInfo;
            $signedTransactionInfo = base64_decode($signedTransactionInfo);
            $signedTransactionInfo = json_decode($signedTransactionInfo);
            $key = $signedTransactionInfo->originalTransactionId;
            $user = User::where('transaction', $key)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }
            $user->subscribed = 'true';
            $user->save();

            $response = new Response();
            $response->content = $jws;
            $response->save();

        }catch (\Exception $e)
        {
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
        $emailContent = $this->getEmailContent();

        // Create a new instance of the email parser
        $parser = new Parser();
        $parser->setText($emailContent);


        $rawHeaderTo = $parser->getHeader('to');
        $arrayHeaderTo = $parser->getAddresses('to');
        $rawHeaderFrom = $parser->getHeader('from');
        $sender = $parser->getHeader('from');
        $subject = $parser->getHeader('subject');
        $body = $parser->getMessageBody('text');

        $email = [
            'sender' => $sender,
            'subject' => $subject,
            'body' => $body,
            'rawHeaderTo' => $rawHeaderTo,
            'arrayHeaderTo' => $arrayHeaderTo,
            'rawHeaderFrom' => $rawHeaderFrom,

        ];
        return view('ses', compact('email'));
    }
}
