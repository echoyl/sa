import { MenuFormColumn } from '@/pages/dev/menu';
import request, { currentUser, getFullUrl, requestHeaders } from '@/services/ant-design-pro/sadmin';
import {
  CheckCircleOutlined,
  CloseCircleOutlined,
  CloudDownloadOutlined,
  CloudUploadOutlined,
  DeleteOutlined,
  LoadingOutlined,
  PlusOutlined,
  SettingOutlined,
} from '@ant-design/icons';
import { FormattedMessage, Link, useModel } from '@umijs/max';
import { App, Button, Space, Upload } from 'antd';
import { cloneDeep, isString } from 'lodash';
import React, { useContext, useState } from 'react';
import ButtonDrawer from '../../action/buttonDrawer';
import CustomerColumnRender from '../../action/customerColumn';
import { SaForm } from '../../posts/post';
import { SaContext } from '../../posts/table';
import { DndContext } from '../dnd-context';
import { ToolbarColumnTitle } from './title';

export const ToolBarDom = (props) => {
  const {
    selectedRows,
    selectRowBtns = [],
    remove,
    switchState,
    deleteable = true,
    devEnable: pdevEnable,
  } = props;
  const { searchData } = useContext(SaContext);
  //console.log('props.btns', selectRowBtns);
  const selectedIds = selectedRows.map((item) => item.id);
  const { initialState } = useModel('@@initialState');
  const devEnable =
    pdevEnable && !initialState?.settings?.devDisable && initialState?.settings?.dev;
  return (
    <Space>
      <Space key="selectbar_count">
        <span>选择</span>
        <a style={{ fontWeight: 600 }}>{selectedRows.length}</a>
        <span>项</span>
      </Space>
      {searchData?.states?.map((stateButton, k) => {
        return (
          <Button
            key={'state_' + k}
            size="small"
            icon={!stateButton.value ? <CloseCircleOutlined /> : <CheckCircleOutlined />}
            type={!stateButton.value ? 'dashed' : 'primary'}
            danger={!stateButton.value ? true : false}
            onClick={async () => {
              switchState(
                selectedIds,
                '确定要' + stateButton.label + ':' + selectedRows.length + '条记录吗？',
                stateButton.value,
              );
            }}
          >
            批量{stateButton.label}
          </Button>
        );
      })}

      {deleteable ? (
        <Button
          key="selectbar_delete"
          danger
          type="primary"
          size="small"
          icon={<DeleteOutlined />}
          onClick={async () => {
            remove(selectedIds, '确定要删除:' + selectedRows.length + '条记录吗？');
          }}
        >
          批量删除
        </Button>
      ) : null}

      {selectRowBtns?.map((cbtn, ci) => {
        return (
          <Space key="selectbar" key={'customer_' + ci}>
            {devEnable && (
              <Button size="small" key={cbtn.uid + '_dev'} type="dashed">
                <ToolbarColumnTitle {...cbtn} />
              </Button>
            )}
            <CustomerColumnRender
              key={'customer_' + ci}
              items={cbtn.fieldProps?.items}
              paramExtra={{ ids: selectedIds }}
              record={{ ids: selectedIds }}
            />
          </Space>
        );
      })}

      {devEnable && selectRowBtns.length < 1 && (
        <Button key="toolbar_add_dev" type="dashed" size="small">
          <ToolbarColumnTitle title="+" />
        </Button>
      )}
    </Space>
  );
};

const ExportButton = ({ title = '导出', fieldProps = { post: {} }, values = {}, url = '' }) => {
  const { modal } = App.useApp();
  const { searchFormRef } = useContext(SaContext);
  return (
    <Button
      key="exportButton"
      icon={<CloudDownloadOutlined />}
      onClick={async () => {
        modal.confirm({
          title: '温馨提示！',
          content: '确定要导出吗？',
          onOk: async () => {
            const { post = {} } = fieldProps;
            const search = searchFormRef?.current?.getFieldsFormatValue();
            await request.post(url + '/export', { data: { ...values, ...post, ...search } });
          },
        });
      }}
    >
      {title}
    </Button>
  );
};

//导入按钮

const ImportButton = ({ title = '导入', url = '', uploadProps: ups = {} }) => {
  const uploadProps = {
    name: 'file',
    action: getFullUrl(url + '/import'),
    headers: requestHeaders(),
    itemRender: () => '',
    ...ups,
  };
  const [loading, setLoading] = useState(false);
  const { message } = App.useApp();
  const { actionRef } = useContext(SaContext);
  return (
    <Upload
      key="importButton"
      {...uploadProps}
      onChange={(info) => {
        setLoading(true);
        if (info.file.status !== 'uploading') {
          //console.log(info.file, info.fileList);
        }
        if (info.file.status === 'done') {
          //console.log('donenenene');
          setLoading(false);
          const { code, msg, data } = info.file.response;
          if (!code) {
            //设置预览图片路径未服务器路径
            message.success(`${info.file.name} ${msg}`);
            actionRef.current?.reload();
          } else {
            //上传失败了
            message.error(msg);
          }
        } else if (info.file.status === 'error') {
          setLoading(false);
          message.error(`${info.file.name} file upload failed.`);
        }
      }}
    >
      <Button icon={loading ? <LoadingOutlined /> : <CloudUploadOutlined />}>{title}</Button>
    </Upload>
  );
};

export const toolBarRender = (props) => {
  //导出按钮
  const {
    addable = true,
    openType = 'drawer',
    setCurrentRow,
    handleModalVisible,
    path,
    toolBarButton = [],
    url,
    paramExtra,
    enums,
    table_menu_key,
    tableMenuId,
    selectedRowKeys,
    devEnable: pdevEnable,
    pageMenu,
  } = props;
  const createButton = (
    <Button type="primary" key="primary">
      <Space>
        <PlusOutlined />
        <FormattedMessage id="pages.searchTable.new" />
      </Space>
    </Button>
  );
  const { initialState, setInitialState } = useModel('@@initialState');
  const devEnable =
    pdevEnable && !initialState?.settings?.devDisable && initialState?.settings?.dev;
  const values = { ...paramExtra, ids: selectedRowKeys };
  if (table_menu_key) {
    values[table_menu_key] = tableMenuId;
  }
  const _btns = cloneDeep(toolBarButton);
  if (devEnable) {
    _btns.push({
      valueType: 'devsetting',
      title: <SettingOutlined />,
      key: 'devsetting',
    });
    if (_btns.length <= 1) {
      _btns.push({
        valueType: 'devadd',
        title: '+',
        key: 'devadd',
      });
    }
  }
  const render = () => {
    const btns = [];

    const MenuForm = (mprops) => {
      const { contentRender, setOpen } = mprops;
      return (
        <SaForm
          formColumns={MenuFormColumn}
          url="dev/menu/show"
          dataId={pageMenu?.id}
          paramExtra={{ id: pageMenu?.id }}
          postExtra={{ id: pageMenu?.id }}
          showTabs={false}
          grid={false}
          devEnable={false}
          width={1600}
          msgcls={async ({ code, data }) => {
            if (!code) {
              setOpen(false);
              const msg = await currentUser();
              //const msg = await cuser();
              setInitialState((s) => ({
                ...s,
                currentUser: msg.data,
              }));
            }
            return;
          }}
          formProps={{
            contentRender,
            submitter: {
              //移除默认的重置按钮，点击重置按钮后会重新请求一次request
              render: (props, doms) => {
                return [
                  <Button key="rest" type="default" onClick={() => setOpen?.(false)}>
                    关闭
                  </Button>,
                  doms[1],
                ];
              },
            },
          }}
        />
      );
    };

    _btns?.forEach((btn, index) => {
      //console.log('btn', btn);
      if (devEnable && isString(btn.title)) {
        btn.title = <ToolbarColumnTitle {...btn} />;
      }

      if (btn.valueType == 'export') {
        btns.push(<ExportButton key="export" {...btn} url={url} values={values} />);
      }
      if (btn.valueType == 'import') {
        btns.push(<ImportButton key="import" {...btn} url={url} />);
      }
      if (btn.valueType == 'devadd') {
        btns.push(
          <Button key={btn.key} type="dashed">
            {btn.title}
          </Button>,
        );
      }
      if (btn.valueType == 'devsetting') {
        btns.push(
          <ButtonDrawer
            key="devsetting"
            trigger={
              <Button type="dashed" danger>
                {btn.title}
              </Button>
            }
            width={1600}
          >
            <MenuForm />
          </ButtonDrawer>,
        );
      }
      if (btn.valueType == 'toolbar') {
        //console.log('toolbar btn', btn);
        if (devEnable) {
          btns.push(
            <Button key={btn.uid + '_dev'} type="dashed">
              <ToolbarColumnTitle {...btn} />
            </Button>,
          );
        }
        btns.push(
          <CustomerColumnRender
            key={'ccrender_' + index}
            items={btn.fieldProps?.items}
            paramExtra={values}
            record={enums}
          />,
        );
      }
    });

    typeof props.toolBar == 'function' && btns.push(props.toolBar({ enums }));
    if (addable) {
      if (openType == 'drawer' || openType == 'modal') {
        btns.push(
          React.cloneElement(createButton, {
            onClick: async (e: any) => {
              setCurrentRow({ id: 0 });
              handleModalVisible(true);
            },
          }),
        );
      } else {
        btns.push(
          <Link key="add" to={path ? path + '/0' : './0'}>
            {createButton}
          </Link>,
        );
      }
    }
    return [<DndContext key="toolbar">{btns}</DndContext>];
  };
  return render;
};
