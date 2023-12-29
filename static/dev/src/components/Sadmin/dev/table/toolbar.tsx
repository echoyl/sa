import request, { getFullUrl, requestHeaders } from '@/services/ant-design-pro/sadmin';
import {
  CheckCircleOutlined,
  CloseCircleOutlined,
  DeleteOutlined,
  DownloadOutlined,
  LoadingOutlined,
  PlusOutlined,
  UploadOutlined,
} from '@ant-design/icons';
import { FormattedMessage, Link } from '@umijs/max';
import { App, Button, Space, Upload } from 'antd';
import React, { useContext, useState } from 'react';
import CustomerColumnRender from '../../action/customerColumn';
import { SaContext } from '../../posts/table';

export const ToolBarDom = (props) => {
  const { selectedRows, selectRowBtns = [], remove, switchState, deleteable = true } = props;
  const { searchData } = useContext(SaContext);
  //console.log('props.btns', selectRowBtns);
  const selectedIds = selectedRows.map((item) => item.id);

  return (
    <Space>
      <Space>
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
      {selectRowBtns?.map((cbtn, ci) => {
        return (
          <CustomerColumnRender
            key={'customer_' + ci}
            items={cbtn.fieldProps?.items}
            paramExtra={{ ids: selectedIds }}
            record={{ ids: selectedIds }}
          />
        );
      })}
      {deleteable ? (
        <Button
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
    </Space>
  );
};

const ExportButton = ({ title = '导出', fieldProps = { post: {} }, values = {}, url = '' }) => {
  const { modal } = App.useApp();
  const { searchFormRef } = useContext(SaContext);
  return (
    <Button
      key="exportButton"
      icon={<DownloadOutlined />}
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

const ImportButton = ({ title = '导入', url = '' }) => {
  const uploadProps = {
    name: 'file',
    action: getFullUrl(url + '/import'),
    headers: requestHeaders(),
    itemRender: () => '',
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
      <Button icon={loading ? <LoadingOutlined /> : <UploadOutlined />}>{title}</Button>
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
    toolBarButton,
    url,
    paramExtra,
    enums,
    table_menu_key,
    tableMenuId,
    selectedRowKeys,
  } = props;
  const createButton = (
    <Button type="primary" key="primary">
      <Space>
        <PlusOutlined />
        <FormattedMessage id="pages.searchTable.new" />
      </Space>
    </Button>
  );
  const values = { ...paramExtra, ids: selectedRowKeys };
  if (table_menu_key) {
    values[table_menu_key] = tableMenuId;
  }
  const render = () => {
    const btns = [];
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
    toolBarButton?.forEach((btn, index) => {
      //console.log('btn', btn);

      if (btn.valueType == 'export') {
        btns.push(<ExportButton {...btn} url={url} values={values} />);
      }
      if (btn.valueType == 'import') {
        btns.push(<ImportButton {...btn} url={url} />);
      }
      if (btn.valueType == 'toolbar') {
        //console.log('toolbar btn', btn);
        btns.push(
          <CustomerColumnRender items={btn.fieldProps?.items} paramExtra={values} record={enums} />,
        );
      }
    });
    //typeof toolBar == 'function' && btns.push(toolBar({ data, enums }));
    return btns;
  };
  return render;
};
