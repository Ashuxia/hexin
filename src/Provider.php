<?php

namespace Ashuxia\ThirdLogin;

use SocialiteProviders\Manager\Contracts\ConfigInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

use SocialiteProviders\Manager\OAuth2\User;
use Laravel\Socialite\Two\ProviderInterface;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;


class Provider extends AbstractProvider implements ProviderInterface
{
    /**
     * Provider标识
     */
    const IDENTIFIER = 'WEIXIN';

    //应用的openid
    protected $openId;
    //定义授权作用域
    protected $scopes = ['snsapi_userinfo'];
    //PC端还是移动端
    protected $device = 'pc';
    //授权地址
    protected $auth_url = '';

    /**
     * 拼接授权链接地址
     * @param string $url
     * @param string $state
     * @return string
     */
    protected function buildAuthUrlFromBase($url, $state)
    {
        $query = http_build_query($this->getCodeFields($state), '', '&', $this->encodingType);

        return $url . '?' . $query . '#wechat_redirect';
    }

    /**
     * 获取access token的api地址
     * @return string
     */
    protected function getTokenUrl()
    {
        return 'https://api.weixin.qq.com/sns/oauth2/access_token';
    }

    /**
     * 用token获取userinfo
     * @param string $token
     * @return mixed
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://api.weixin.qq.com/sns/userinfo', [
            'query' => [
                'access_token' => $token,
                'openid' => $this->openId,
                'lang' => 'zh_CN',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * 将微信返回的userinfo转成Auth/User对象
     * @param array $user
     * @return $this
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'openid' => $user['openid'],
            'nickname' => isset($user['nickname']) ? $user['nickname'] : null,
            'avatar' => isset($user['headimgurl']) ? $user['headimgurl'] : null,
            'name' => null,
            'email' => null,
        ]);
    }

    /**
     * 获取调用access token api时的参数
     * @param string $code
     * @return array
     */
    protected function getTokenFields($code)
    {
        return [
            'appid' => $this->clientId, 'secret' => $this->clientSecret,
            'code' => $code, 'grant_type' => 'authorization_code',
        ];
    }

    /**
     * 获取授权链接
     * @param string $state
     * @return string
     */
    protected function getAuthUrl($state)
    {
        if ($this->device == 'pc') {
            $this->auth_url = 'https://open.weixin.qq.com/connect/qrconnect';
        } else {
            $this->auth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize';
        }
        return $this->buildAuthUrlFromBase($this->auth_url, $state);
    }

    //===========================================================
    //以下为public方法，外部可根据需要调用
    //===========================================================

    /**
     * 获取授权地址中要传递的参数
     * @param null $state
     * @return array
     */
    protected function getCodeFields($state = null)
    {
        $options = [
            'appid' => $this->clientId, 'redirect_uri' => $this->redirectUrl,
            'response_type' => 'code',
            'scope' => $this->formatScopes($this->scopes, $this->scopeSeparator),
            'state' => $state,
        ];
        return $options;
    }

    /**
     * 获取access token
     * @param string $code
     * @return mixed
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'query' => $this->getTokenFields($code),
        ]);

        $this->credentialsResponseBody = json_decode($response->getBody(), true);
        $this->openId = $this->credentialsResponseBody['openid'];

        return $this->credentialsResponseBody;
    }

    /**
     * 提供给外部定义scope
     * @param array $scopes
     * @return $this
     */
    public function scopes(array $scopes)
    {
        $this->scopes = array_unique($scopes);

        return $this;
    }

    /**
     * 重写setConfig方法，在原有的基础上，增加对
     * 'device' 参数的解析
     * @param ConfigInterface $config
     * @return $this
     */
    public function setConfig(ConfigInterface $config)
    {
        $config = $config->get();

        $this->config = $config;
        $this->clientId = $config['client_id'];
        $this->clientSecret = $config['client_secret'];
        $this->redirectUrl = $config['redirect'];

        if (isset($config['device'])) {
            $this->device = $config['device'];
        }

        return $this;
    }

    public function getOpenId()
    {
        return $this->openId;
    }

    public function setOpenId($openId)
    {
        $this->openId = $openId;
        return $this;
    }

    public function getScopes()
    {
        return $this->scopes;
    }

    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
        return $this;
    }

    public function getDevice()
    {
        return $this->device;
    }

    public function setDevice($device)
    {
        $this->device = $device;
        return $this;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }
}
