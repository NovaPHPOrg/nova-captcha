<?php

namespace nova\plugin\captcha;

use nova\framework\request\Response;
use nova\plugin\cookie\Session;

class Captcha
{
    private int $result = 0;

    public function getResult(): string
    {
        return $this->result;
    }

    public static function verify(string $scene, int $code): bool
    {
        $result = Session::getInstance()->get($scene,0);
        Session::getInstance()->delete($scene);
        return $code === $result;
    }

    /**
     */
    public function create(string $scene): Response
    {
        $image = imagecreate(200, 100);
        imagecolorallocate($image, 0, 0, 0);

        for ($i = 0; $i <= 9; $i++) {
            imageline($image, rand(0, 200), rand(0, 100), rand(0, 200), rand(0, 100), $this->color($image));
        }

        for ($i = 0; $i <= 100; $i++) {
            imagesetpixel($image, rand(0, 200), rand(0, 100), $this->color($image));
        }

        $str = $this->generateCode($scene);

        $ttf = ROOT_PATH . "/nova/plugin/captcha/fonts/Bitsumishi.ttf";

        for ($i = 0; $i < 4; $i++) {
            imagettftext($image, rand(20, 38), rand(0, 30), $i * 50 + 25, rand(30, 70), $this->color($image), $ttf, $str[$i]);
        }


        imagejpeg($image);
        imagedestroy($image);

        return Response::asNone([
            "Content-type" => "image/jpeg",
        ]);
    }

    private function color($image): int
    {
        return imagecolorallocate($image, rand(127, 255), rand(127, 255), rand(127, 255));
    }

    private function generateCode(string $scene): string
    {
        $operators = ["+", "-", "*"];
        $num1 = rand(0, 9);
        $num2 =  rand(0, 9);
        $operator = $operators[rand(0, 2)];

        $str = $num1 . $operator . $num2 . "=?";
        if ($operator == "+") {
            $this->result = $num1 + $num2;
        } else if ($operator == "*") {
            $this->result = $num1 * $num2;
        } else{
            $this->result = $num1 - $num2;
        }

        if ($this->result == 0){
            return $this->generateCode($scene);
        }

        Session::getInstance()->set($scene, $this->result, 300);
        return $str;
    }
}