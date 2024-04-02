import { SelectLang as UmiSelectLang, useModel } from '@umijs/max';
import { Space } from 'antd';
import React from 'react';
import NoticeIconView from '../NoticeIcon';
import DevSwitch from '../Sadmin/dev/switch';
import ThemeSwitch from '../Sadmin/themSwitch';
import Avatar from './AvatarDropdown';
import styles from './index.less';
export type SiderTheme = 'light' | 'dark';

const GlobalHeaderRight: React.FC = () => {
  const { initialState } = useModel('@@initialState');

  if (!initialState || !initialState.settings) {
    return null;
  }

  const { navTheme, layout, lang = true } = initialState.settings;
  let className = styles.right;

  if ((navTheme === 'realDark' && layout === 'top') || layout === 'mix') {
    className = `${styles.right}  ${styles.dark}`;
  }

  return (
    <Space className={className}>
      <DevSwitch />

      <Avatar menu={true} />
      {lang && (
        <UmiSelectLang
          style={{
            padding: 4,
          }}
        />
      )}
      <ThemeSwitch />
      <NoticeIconView />
      <span> </span>
    </Space>
  );
};

export default GlobalHeaderRight;
