import request from '@/services/ant-design-pro/sadmin';
import { css } from '@emotion/css';
import { Dropdown, DropdownProps } from 'antd';
import { ItemType } from 'antd/es/menu/hooks/useItems';
import React, { ReactNode, createContext, useContext, useState, useTransition } from 'react';
import message from '../../message';

export type tableDesignerInstance = {
  pageMenu?: { [key: string]: any };
  sort?: (id: number, cls: any[]) => void;
  setTbColumns?: any;
  getTableColumnsRender?: any;
  edit?: (data: { [key: string]: any }) => void;
  editUrl?: string;
  reflush?: (data: { [key: string]: any }) => void;
  deleteUrl?: string;
  delete?: (data: { [key: string]: any }) => void;
};

export function useTableDesigner(props: tableDesignerInstance) {
  const { setTbColumns, getTableColumnsRender, pageMenu } = props;
  const editUrl = 'dev/menu/editTableColumn';
  const deleteUrl = 'dev/menu/deleteTableColumn';
  const reflush = (data) => {
    //重新设置列表列
    setTbColumns?.(getTableColumnsRender?.(data.tableColumns));
    //更新schema
    pageMenu.schema = data.schema;
    pageMenu.data.tableColumns = data.tableColumns;
  };
  return {
    ...props,
    editUrl,
    deleteUrl,
    reflush,
    sort: (id: number, columns: any) => {
      //后台请求
      setTbColumns(getTableColumnsRender(columns));
      request.post('dev/menu/sortTableColumns', {
        data: { columns, id },
        then: () => {},
      });
    },
    edit: async (data: { [key: string]: any }) => {
      //后台请求
      await request.post(editUrl, {
        data,
        then: ({ data, code, msg }) => {
          if (!code) {
            reflush(data);
          } else {
            message?.error(msg);
          }
        },
      });
      return;
    },
  };
}

interface SchemaSettingsContextProps<T = any> {
  setVisible?: any;
  visible?: any;
}

export const SchemaSettingsContext = createContext<SchemaSettingsContextProps>(null);

export function useSchemaSettings<T = any>() {
  return useContext(SchemaSettingsContext) as SchemaSettingsContextProps<T>;
}

interface SchemaSettingsProviderProps {
  setVisible?: any;
  visible?: any;
  children?: ReactNode;
}

export const SchemaSettingsProvider: React.FC<SchemaSettingsProviderProps> = (props) => {
  const { children, ...others } = props;
  return (
    <SchemaSettingsContext.Provider value={{ ...others }}>
      {children}
    </SchemaSettingsContext.Provider>
  );
};

export interface SchemaSettingsProps {
  title?: any;
  children?: ReactNode;
  items?: ItemType[];
}

export const SchemaSettingsDropdown: React.FC<SchemaSettingsProps> = (props) => {
  const { title, items, ...others } = props;
  const [visible, setVisible] = useState(false);
  const [, startTransition] = useTransition();

  const changeMenu: DropdownProps['onOpenChange'] = (nextOpen: boolean, info) => {
    // 在 antd v5.8.6 版本中，点击菜单项不会触发菜单关闭，但是升级到 v5.12.2 后会触发关闭。查阅文档发现
    // 在 v5.11.0 版本中增加了一个 info.source，可以通过这个来判断一下，如果是点击的是菜单项就不关闭菜单，
    // 这样就可以和之前的行为保持一致了。
    // 下面是模仿官方文档示例做的修改：https://ant.design/components/dropdown-cn
    if (info.source === 'trigger' || nextOpen) {
      // 当鼠标快速滑过时，终止菜单的渲染，防止卡顿
      startTransition(() => {
        setVisible(nextOpen);
      });
    }
  };

  return (
    <SchemaSettingsProvider visible={visible} setVisible={setVisible} {...others}>
      <Dropdown
        open={visible}
        onOpenChange={(open, info) => {
          changeMenu(open, info);
        }}
        overlayClassName={css`
          .ant-dropdown-menu-item-group-list {
            max-height: 300px;
            overflow-y: auto;
          }
        `}
        menu={{ items }}
      >
        {typeof title === 'string' ? <span>{title}</span> : title}
      </Dropdown>
    </SchemaSettingsProvider>
  );
};
