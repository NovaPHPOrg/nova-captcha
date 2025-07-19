<?php

/*
 * Copyright (c) 2025. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

declare(strict_types=1);

namespace nova\plugin\captcha;

use nova\framework\http\Response;
use nova\plugin\cookie\Session;

/**
 * 验证码生成和验证类
 *
 * 提供数学运算验证码的生成和验证功能，包括：
 * - 生成数学运算验证码图片
 * - 验证用户输入的验证码
 * - 会话管理和验证码存储
 *
 * 验证码格式：数字 + 运算符 + 数字 = ?
 * 支持的运算符：+、-、*
 *
 * @author Ankio
 * @version 1.0
 * @since 2025-01-01
 */
class Captcha
{
    /**
     * 验证码计算结果
     * 存储当前生成的验证码的正确答案
     */
    private int $result = 0;

    /**
     * 获取验证码计算结果
     *
     * @return int 返回当前验证码的正确答案
     */
    public function getResult(): int
    {
        return $this->result;
    }

    /**
     * 验证用户输入的验证码
     *
     * 从会话中获取存储的验证码结果，与用户输入进行比较
     * 验证完成后自动删除会话中的验证码数据
     *
     * @param  int  $code 用户输入的验证码答案
     * @return bool 验证成功返回true，失败返回false
     */
    public static function verify(int $code): bool
    {
        $result = Session::getInstance()->get("captcha_item", 0);
        Session::getInstance()->delete("captcha_item");
        return $code === $result;
    }

    /**
     * 生成验证码图片
     *
     * 创建包含数学运算的验证码图片，流程：
     * 1. 创建图片画布
     * 2. 添加干扰线和噪点
     * 3. 生成数学运算表达式
     * 4. 使用TTF字体渲染文字
     * 5. 输出JPEG格式图片
     *
     * @return Response 返回包含验证码图片的HTTP响应
     */
    public function create(): Response
    {
        // 创建200x100像素的图片画布
        $image = imagecreate(200, 100);
        imagecolorallocate($image, 0, 0, 0);

        // 添加干扰线
        for ($i = 0; $i <= 9; $i++) {
            imageline($image, rand(0, 200), rand(0, 100), rand(0, 200), rand(0, 100), $this->color($image));
        }

        // 添加噪点
        for ($i = 0; $i <= 100; $i++) {
            imagesetpixel($image, rand(0, 200), rand(0, 100), $this->color($image));
        }

        // 生成验证码表达式
        $str = $this->generateCode();

        // 使用TTF字体文件路径
        $ttf = ROOT_PATH . "/nova/plugin/captcha/Bitsumishi.ttf";

        // 渲染验证码文字，每个字符使用不同的角度和位置
        for ($i = 0; $i < 4; $i++) {
            imagettftext($image, rand(20, 38), rand(0, 30), $i * 50 + 25, rand(30, 70), $this->color($image), $ttf, $str[$i]);
        }

        // 输出JPEG格式图片
        ob_start();
        imagejpeg($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return Response::asRaw($imageData, [
            "Content-Type" => "image/jpeg",
        ]);
    }

    /**
     * 生成随机颜色
     *
     * 生成RGB值在127-255之间的随机颜色，确保文字清晰可见
     *
     * @param  resource $image 图片资源
     * @return int      返回颜色标识符
     */
    private function color($image): int
    {
        return imagecolorallocate($image, rand(127, 255), rand(127, 255), rand(127, 255));
    }

    /**
     * 生成验证码表达式
     *
     * 生成数学运算表达式，确保结果不为0
     * 将正确答案存储到会话中，有效期5分钟
     *
     * @return string 返回验证码表达式字符串
     */
    private function generateCode(): string
    {
        $operators = ["+", "-", "*"];
        $num1 = rand(0, 9);
        $num2 =  rand(0, 9);
        $operator = $operators[rand(0, 2)];

        // 构建表达式字符串
        $str = $num1 . $operator . $num2 . "=?";

        // 计算结果
        if ($operator == "+") {
            $this->result = $num1 + $num2;
        } elseif ($operator == "*") {
            $this->result = $num1 * $num2;
        } else {
            $this->result = $num1 - $num2;
        }

        // 如果结果为0，重新生成
        if ($this->result == 0) {
            return $this->generateCode();
        }

        // 将结果存储到会话中，有效期5分钟
        Session::getInstance()->set("captcha_item", $this->result, 300);
        return $str;
    }
}
