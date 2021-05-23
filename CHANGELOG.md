# Yii Cookies Change Log


## 1.2.1 under development

- no changes in this release.


## 1.2.0 May 23, 2021

- Add #27: Add the parameter `$encodeValue` to the `Cookie` constructor and the `Cookie::withRawValue()` method 
  that creates a cookie copy with a new value that will not be encoded (vjik)


## 1.1.0 May 05, 2021

- Add #19: Add the `Yiisoft\Cookies\CookieEncryptor` class to encrypt the value of the cookie and verify that it is tampered (devanych)
- Add #19: Add the `Yiisoft\Cookies\CookieSigner` class to sign the value of the cookie and verify that it is tampered (devanych)
- Add #22: Add the `Yiisoft\Cookies\CookieMiddleware` class to encrypt/sign the value of the cookie and verify that it is tampered (devanych)

## 1.0.0 December 02, 2020

- Initial release.
