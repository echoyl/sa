import { Button } from 'antd';
import { useContext } from 'react';
import ButtonDrawer from '../../action/buttonDrawer';
import ButtonModal from '../../action/buttonModal';
import { SaForm } from '../../posts/post';
import { SaContext } from '../../posts/table';

const InnerForm = (props) => {
  const {
    setOpen,
    contentRender,
    formColumns,
    url,
    currentRow,
    paramExtra,
    tabs,
    postExtra,
    addable,
    editable,
  } = props;
  const { actionRef, formRef } = useContext(SaContext);
  return (
    <SaForm
      {...props}
      msgcls={({ code }) => {
        if (!code) {
          actionRef.current?.reload();
          //设置弹出层关闭，本来会触发table重新加载数据后会关闭弹层，但是如果数据重载过慢的话，这个会感觉很卡所以在这里直接设置弹层关闭
          setOpen(false);
          return;
        }
      }}
      beforeGet={(data) => {
        if (!data) {
          //没有data自动关闭弹出层
          setOpen?.(false);
        }
      }}
      formColumns={formColumns}
      tabs={tabs}
      formRef={formRef}
      actionRef={actionRef}
      paramExtra={{ ...currentRow, ...paramExtra }}
      postExtra={{ ...currentRow, ...postExtra }}
      url={url}
      showTabs={tabs?.length > 1 ? true : false}
      formProps={{
        contentRender,
        submitter:
          (!editable && currentRow.id) ||
          (currentRow.readonly && currentRow.id) ||
          (!currentRow.id && !addable)
            ? false
            : {
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
      align="left"
      dataId={currentRow.id}
      pageType="drawer"
    />
  );
};

export const TableForm = (props) => {
  const {
    openType,
    createModalVisible,
    currentRow,
    handleModalVisible,
    name,
    openWidth = props.openType == 'drawer' ? 754 : 754,
    formColumns,
    url,
    paramExtra,
    tabs,
    postExtra,
    editable = true,
    addable = true,
  } = props;
  return (
    <>
      {openType == 'modal' && (
        <ButtonModal
          open={createModalVisible}
          title={
            (currentRow.id ? (currentRow.readonly ? '查看' : '编辑') : '新增') +
            (name ? ' - ' + name : '')
          }
          width={openWidth}
          afterOpenChange={(open) => {
            handleModalVisible(open);
          }}
        >
          <InnerForm
            {...props}
            formColumns={formColumns}
            url={url + '/show'}
            currentRow={currentRow}
            paramExtra={paramExtra}
            tabs={tabs}
            postExtra={postExtra}
            editable={editable}
            addable={addable}
          />
        </ButtonModal>
      )}
      {openType == 'drawer' && (
        <ButtonDrawer
          open={createModalVisible}
          title={
            (currentRow.id ? (currentRow.readonly ? '查看' : '编辑') : '新增') +
            (name ? ' - ' + name : '')
          }
          width={openWidth}
          afterOpenChange={(open) => {
            handleModalVisible(open);
          }}
        >
          <InnerForm
            {...props}
            formColumns={formColumns}
            url={url + '/show'}
            currentRow={currentRow}
            paramExtra={paramExtra}
            tabs={tabs}
            postExtra={postExtra}
            editable={editable}
            addable={addable}
          />
        </ButtonDrawer>
      )}
    </>
  );
};