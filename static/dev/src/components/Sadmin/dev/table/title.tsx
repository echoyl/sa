import {
  DeleteColumnOutlined,
  DragOutlined,
  EditOutlined,
  InsertRowRightOutlined,
  MenuOutlined,
  SettingOutlined,
} from '@ant-design/icons';
import { css } from '@emotion/css';
import { useModel } from '@umijs/max';
import { Space } from 'antd';
import { ItemType } from 'antd/es/menu/hooks/useItems';
import classNames from 'classnames';
import React, { useContext } from 'react';
import { SaDevContext } from '..';
import Confirm from '../../action/confirm';
import ConfirmForm from '../../action/confirmForm';
import { CustomerColumnRenderDevReal } from '../../action/customerColumn/dev';
import { SaContext } from '../../posts/table';
import { DragHandler, SortableItem } from '../dnd-context/SortableItem';
import { devBaseTableFormColumns, getModelRelations } from './baseFormColumns';
import { SchemaSettingsContext, SchemaSettingsDropdown } from './designer';
export const designerCss = css`
  position: relative;
  min-width: 60px;
  &:hover {
    > .general-schema-designer {
      display: block;
    }
  }
  > .general-schema-designer {
    position: absolute;
    top: 0;
    /*top: -16px !important;*/
    right: 0;
    /*right: -16px !important;*/
    bottom: 0;
    /*bottom: -16px !important;*/
    left: 0;
    /*left: -16px !important;*/
    z-index: 999;
    display: none;
    background: rgba(241, 139, 98, 0.12) !important;
    border: 0 !important;
    pointer-events: none;
    > .general-schema-designer-icons {
      position: absolute;
      top: 2px;
      right: 2px;
      line-height: 16px;
      pointer-events: all;
      .ant-space-item {
        align-self: stretch;
        width: 16px;
        color: #fff;
        line-height: 16px;
        text-align: center;
        background-color: rgb(241, 139, 98);
      }
    }
  }
`;

const overrideAntdCSS = css`
  & .ant-space-item .anticon {
    margin: 0;
  }

  &:hover {
    display: block !important;
  }
`;

const BaseForm = (props) => {
  const { title, uid = '', afterUid = '' } = props;
  const {
    tableDesigner: { pageMenu, reflush, editUrl = '' },
  } = useContext(SaContext);
  const { setting } = useContext(SaDevContext);
  const { setVisible } = useContext(SchemaSettingsContext);
  const columns = devBaseTableFormColumns({
    model_id: pageMenu?.model_id,
    dev: setting?.dev,
  });
  const trigger = React.cloneElement(title, {
    key: 'trigger',
    ...title.props,
    onClick: async (e: any) => {
      setVisible(false);
    },
  });
  //无uid表示插入列
  const value = uid ? {} : JSON.parse(pageMenu?.schema?.table_config)?.find((v) => v.uid == uid);

  //console.log('tableDesigner?.pageMenu', setTbColumns, getTableColumnsRender);

  return (
    <ConfirmForm
      trigger={trigger}
      formColumns={columns}
      value={value}
      postUrl={editUrl}
      data={{ id: pageMenu?.id, uid, afterUid }}
      callback={({ data }) => {
        reflush(data);
      }}
    />
  );
};

const MoreForm = (props) => {
  const { title, uid } = props;
  const {
    tableDesigner: { pageMenu, edit },
  } = useContext(SaContext);
  const { setting } = useContext(SaDevContext);
  const relations = getModelRelations(pageMenu?.model_id, setting?.dev);
  const { allMenus = [] } = setting?.dev;
  //获取值
  const value = JSON.parse(pageMenu?.schema?.table_config)?.find?.((v) => v.uid == uid);
  const onChange = async (values) => {
    value.props = values;
    const data = { base: { ...value, id: pageMenu?.id, uid, props: values } };
    //return;
    await edit(data);
  };
  //console.log('value', value, uid, JSON.parse(pageMenu?.schema?.table_config));
  const fieldProps = {
    value: value?.props,
    onChange,
    btnText: title,
    relationModel: relations,
    allMenus,
  };
  return <CustomerColumnRenderDevReal fieldProps={fieldProps} />;
};

const DeleteColumn = (props) => {
  const { title, uid } = props;
  const {
    tableDesigner: { pageMenu, reflush, deleteUrl = '' },
  } = useContext(SaContext);
  return (
    <Confirm
      trigger={title}
      url={deleteUrl}
      data={{ base: { id: pageMenu?.id, uid } }}
      msg="确定要删除该列吗"
      callback={({ data }) => {
        reflush(data);
      }}
    />
  );
};

const DevTableColumnTitle = (props) => {
  const { title, uid } = props;
  const designable = true;

  const items: ItemType[] = [
    {
      label: (
        <BaseForm
          title={
            <Space>
              <EditOutlined />
              <span>基本信息</span>
            </Space>
          }
          uid={uid}
        />
      ),
      key: 1,
    },
    {
      label: (
        <MoreForm
          title={
            <Space>
              <SettingOutlined />
              <span>更多设置</span>
            </Space>
          }
          uid={uid}
        />
      ),
      key: 2,
    },
    {
      label: (
        <BaseForm
          title={
            <Space>
              <InsertRowRightOutlined />
              <span>向后插入列</span>
            </Space>
          }
          afterUid={uid}
        />
      ),
      key: 3,
    },
    {
      type: 'divider',
    },
    {
      label: (
        <DeleteColumn
          title={
            <Space>
              <DeleteColumnOutlined />
              <span>删除该列</span>
            </Space>
          }
          uid={uid}
        />
      ),
      key: 4,
      danger: true,
    },
  ];
  return !designable ? (
    title
  ) : (
    <SortableItem className={designerCss} id={uid} eid={uid}>
      <div className={classNames('general-schema-designer', overrideAntdCSS)}>
        <div className={'general-schema-designer-icons'}>
          <Space size={3} align={'center'}>
            <DragHandler>
              <DragOutlined role="button" aria-label={'drag-handler'} />
            </DragHandler>
            <SchemaSettingsDropdown
              title={<MenuOutlined role="button" style={{ cursor: 'pointer' }} />}
              items={items}
            />
          </Space>
        </div>
      </div>
      <div role="button">{title}</div>
    </SortableItem>
  );
};

export const TableColumnTitle = (props) => {
  const { initialState } = useModel('@@initialState');
  const dev = initialState?.settings?.dev ? true : false;
  return dev ? <DevTableColumnTitle {...props} /> : <>{props.title}</>;
};
