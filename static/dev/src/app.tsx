import { Footer, RightContent } from '@/components';

import {
  Settings as LayoutSettings,
  ProConfigProvider,
  ProProvider,
  SettingDrawer,
} from '@ant-design/pro-components';
import { Link, RunTimeLayoutConfig, history } from '@umijs/max';
import { App, ConfigProvider } from 'antd';
import dayjs from 'dayjs';
import 'dayjs/locale/zh-cn';
import { useContext } from 'react';
import defaultSettings, { lightDefaultToken } from '../config/defaultSettings';
import ModalJson from './components/Sadmin/action/modalJson';
import { SaDevContext } from './components/Sadmin/dev';
import { loopMenuItem, saValueTypeMap } from './components/Sadmin/helpers';
import Refresh from './components/Sadmin/refresh';
import { getTheme } from './components/Sadmin/themSwitch';
import request, {
  loginPath,
  currentUser as queryCurrentUser,
} from './services/ant-design-pro/sadmin';
dayjs.locale('zh-cn');
//const isDev = process.env.NODE_ENV === 'development';

/**
 * @see  https://umijs.org/zh-CN/plugins/plugin-initial-state
 * */
export async function getInitialState(): Promise<{
  settings?: Partial<LayoutSettings> & {
    baseurl?: string;
    loginTypeDefault?: string;
    loginBgImgage?: string;
    dev?: { [key: string]: any };
  };
  currentUser?: API.CurrentUser;
  loading?: boolean;
  fetchUserInfo?: () => Promise<API.CurrentUser | undefined>;
}> {
  const fetchUserInfo = async () => {
    try {
      const msg = await queryCurrentUser();
      //const msg = await cuser();
      return msg.data;
    } catch (error) {
      console.log('no login');
      //系统请求已经添加了如果未登录跳转逻辑
      //history.push(loginPath);
    }
    return undefined;
  };
  //获取后台基础配置信息
  const { data: adminSetting } = await request.get('setting');
  // 如果是登录页面，不执行
  //const location = useLocation();
  // console.log(
  //   '获取用户登录信息',
  //   loginPath,
  //   history.location.pathname.replace(adminSetting.baseurl, '/'),
  // );
  //check theme cache
  const theme = getTheme(adminSetting);
  const navTheme =
    theme == 'light'
      ? { navTheme: theme, token: { ...lightDefaultToken } }
      : { navTheme: theme, token: { sider: {}, header: {} } };
  if (history.location.pathname.replace(adminSetting.baseurl, '/') !== loginPath) {
    const currentUser = await fetchUserInfo();
    return {
      fetchUserInfo,
      currentUser,
      settings: { ...defaultSettings, ...adminSetting, ...navTheme },
    };
  }
  return {
    fetchUserInfo,
    settings: { ...defaultSettings, ...adminSetting, ...navTheme },
  };
}

// ProLayout 支持的api https://procomponents.ant.design/components/layout
export const layout: RunTimeLayoutConfig = ({ initialState, setInitialState }) => {
  const values = useContext(ProProvider);
  const checkWaterMark = () => {
    if (initialState?.settings?.watermark) {
      return initialState?.settings?.watermark == 'username'
        ? initialState?.currentUser?.name
        : initialState?.settings?.watermark;
    } else {
      return false;
    }
  };
  return {
    rightContentRender: () => (
      <App>
        <RightContent />
      </App>
    ),
    //disableContentMargin: false,
    waterMarkProps: {
      content: checkWaterMark(),
    },
    footerRender: () => (
      <App>
        <Footer />
      </App>
    ),
    onPageChange: () => {
      // 如果没有登录，重定向到 login
      const pathname = history.location.pathname.replace(initialState?.settings?.baseurl, '/');
      if (!initialState?.currentUser && pathname !== loginPath) {
        console.log('no user');
        history.push({
          pathname: loginPath,
          search: '?redirect=' + history.location.pathname,
        });
      }
    },
    layoutBgImgList: [
      // {
      //   src: 'https://mdn.alipayobjects.com/yuyan_qk0oxh/afts/img/D2LWSqNny4sAAAAAAAAAAAAAFl94AQBr',
      //   left: 85,
      //   bottom: 100,
      //   height: '303px',
      // },
      // {
      //   src: 'https://mdn.alipayobjects.com/yuyan_qk0oxh/afts/img/C2TWRpJpiC0AAAAAAAAAAAAAFl94AQBr',
      //   bottom: -68,
      //   right: -45,
      //   height: '303px',
      // },
      // {
      //   src: 'https://mdn.alipayobjects.com/yuyan_qk0oxh/afts/img/F6vSTbj8KpYAAAAAAAAAAAAAFl94AQBr',
      //   bottom: 0,
      //   left: 0,
      //   width: '331px',
      // },
    ],
    links: initialState?.settings.dev
      ? [
          <Link to={'dev/menu'}>
            <span>菜单</span>
          </Link>,
          <Link to={'dev/model'}>
            <span>模型</span>
          </Link>,
          <Link to={'dev/setting'}>
            <span>配置</span>
          </Link>,
          <Refresh />,
          <ModalJson
            trigger={
              <span style={{ width: '100%', textAlign: 'left', display: 'inline-block' }}>
                json
              </span>
            }
          />,
        ]
      : [],
    menuHeaderRender: undefined,
    menu: {
      request: async (params, defaultMenuData) => {
        const newMenu = loopMenuItem(initialState?.currentUser?.menuData);
        localStorage.setItem('menuData', JSON.stringify(newMenu));
        return newMenu;
      },
      locale: false,
    },
    childrenRender: (children, props) => {
      return (
        <App>
          <ConfigProvider
            theme={
              initialState?.settings?.navTheme == 'light'
                ? {
                    ...initialState?.settings.antdtheme,
                  }
                : {}
            }
          >
            <ProConfigProvider {...values} valueTypeMap={{ ...saValueTypeMap }}>
              <SaDevContext.Provider
                value={{
                  setting: initialState?.settings,
                }}
              >
                {children}
                {initialState?.settings.dev && !props.location?.pathname?.includes('/login') && (
                  <SettingDrawer
                    disableUrlParams
                    enableDarkTheme
                    settings={initialState?.settings}
                    onSettingChange={(settings) => {
                      setInitialState((preInitialState) => ({
                        ...preInitialState,
                        settings: { ...preInitialState.settings, ...settings },
                      }));
                    }}
                  />
                )}
              </SaDevContext.Provider>
            </ProConfigProvider>
          </ConfigProvider>
        </App>
      );
    },
    siderWidth: 208,
    ...initialState?.settings,
  };
};