import PostsForm from '@/components/Sadmin/posts/post';
import request from '@/services/ant-design-pro/sadmin';
import { App, Button } from 'antd';
import { useState } from 'react';

const ClearDevCache = () => {
  const [loading, setLoading] = useState(false);
  const { message } = App.useApp();
  const clear = async () => {
    setLoading(true);
    const { code, msg } = await request.get('dev/menu/clearCache');
    message.info(msg);
    setLoading(false);
  };

  return (
    <Button onClick={clear} loading={loading}>
      清除缓存
    </Button>
  );
};

export default () => {
  return (
    <PostsForm
      url="dev/setting"
      formTitle={false}
      tabs={[
        {
          title: '基础配置',
          formColumns: [
            { title: '系统名称', dataIndex: 'title' },
            { title: '技术支持', dataIndex: 'tech' },
            {
              valueType: 'group',
              columns: [
                { title: '子标题', dataIndex: 'subtitle', width: 'md' },
                { title: '后台前缀', dataIndex: 'baseurl', width: 'md' },
              ],
            },
            {
              valueType: 'group',
              columns: [
                {
                  title: '水印设置',
                  dataIndex: 'watermark',
                  tooltip: '1.username表示后台用户名',
                  width: 'md',
                },
                { title: '腾讯地图key', dataIndex: 'tmap_key', width: 'md' },
              ],
            },
            {
              valueType: 'group',
              columns: [
                {
                  title: '开发模式',
                  valueType: 'switch',
                  dataIndex: 'dev',
                  fieldProps: {
                    defaultChecked: true,
                  },
                },
                {
                  title: '多语言',
                  valueType: 'switch',
                  dataIndex: 'lang',
                  fieldProps: {
                    defaultChecked: true,
                  },
                },
                {
                  title: '分割菜单 - 顶部显示大菜单',
                  valueType: 'switch',
                  dataIndex: 'splitMenus',
                },
              ],
            },
            {
              valueType: 'group',
              columns: [
                { title: 'logo', valueType: 'uploader', dataIndex: 'logo' },
                {
                  title: 'favicons',
                  tooltip: '自行覆盖目录下的favicon.ico 文件',
                  readonly: true,
                  dataIndex: 'favicons',
                },
              ],
            },

            {
              valueType: 'group',
              title: '短信设置',
              columns: [
                {
                  title: '短信平台',
                  valueType: 'select',
                  dataIndex: 'sms_type',
                  fieldProps: {
                    options: [{ label: '阿里云', value: 'aliyun' }],
                  },
                },
                {
                  title: '验证码模板id',
                  dataIndex: 'sms_code_id',
                },
                {
                  title: '模板名称',
                  dataIndex: 'sms_name',
                },
              ],
            },
          ],
        },
        {
          title: '主题配置',
          formColumns: [
            {
              valueType: 'group',
              columns: [
                {
                  valueType: 'switch',
                  title: '自动暗黑模式',
                  dataIndex: 'theme_auto_dark',
                  fieldProps: {
                    defaultChecked: false,
                  },
                },
                {
                  valueType: 'dependency',
                  name: ['theme_auto_dark'],
                  columns: ({ theme_auto_dark }: any) => {
                    return theme_auto_dark
                      ? [
                          {
                            title: '白天时间段',
                            valueType: 'timeRange',
                            dataIndex: 'theme_auto_light_time_range',
                            fieldProps: {
                              minuteStep: 15,
                              secondStep: 10,
                            },
                          },
                        ]
                      : [];
                  },
                },
              ],
            },

            { title: 'Antd主题配置', dataIndex: 'theme', valueType: 'jsonEditor' },
          ],
        },
        {
          title: '登录设置',
          formColumns: [
            {
              valueType: 'group',
              columns: [
                { title: '登录页背景图', valueType: 'uploader', dataIndex: 'loginBgImgage' },
              ],
            },
            {
              title: '显示验证码登录错误次数',
              dataIndex: 'login_error_times',
              tooltip: '登录失败该次数后展示图形验证码输入框，默认数字为3次',
            },
            {
              title: '登录方式',
              valueType: 'checkbox',
              dataIndex: 'loginType',
              fieldProps: {
                options: [
                  { label: '账号密码', value: 'password' },
                  { label: '手机号登录', value: 'phone' },
                ],
              },
            },
            {
              title: '默认登录方式',
              valueType: 'radio',
              dataIndex: 'loginTypeDefault',
              fieldProps: {
                options: [
                  { label: '账号密码', value: 'password' },
                  { label: '手机号登录', value: 'phone' },
                ],
              },
            },
          ],
        },
        {
          title: '缓存设置',
          formColumns: [
            {
              title: '清除缓存',
              renderFormItem: (props) => {
                return <ClearDevCache />;
              },
            },
          ],
        },
      ]}
      msgcls={() => {
        return;
      }}
    />
  );
};
