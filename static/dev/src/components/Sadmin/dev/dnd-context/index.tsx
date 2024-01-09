import {
  DndContext as DndKitContext,
  DragEndEvent,
  DragOverlay,
  rectIntersection,
} from '@dnd-kit/core';
import { Props } from '@dnd-kit/core/dist/components/DndContext/DndContext';
import { arrayMove } from '@dnd-kit/sortable';

import { useContext, useState } from 'react';
import { SaContext } from '../../posts/table';

// const findchild: any = (datas: any[], name: string[] = []) => {
//   const d = name.shift();
//   const fd = datas.find((v) => v.path == d);
//   //console.log('find child', datas, name, d, fd);
//   if (fd) {
//     if (name.length > 0) {
//       return findchild(fd.routes, name);
//     } else {
//       return fd;
//     }
//   } else {
//     return false;
//   }
// };

const useDragEnd = (props?: any) => {
  const { tableDesigner } = useContext(SaContext);
  return (event: DragEndEvent) => {
    const { active, over } = event;
    console.log('dragging', active, over, tableDesigner?.pageMenu);
    if (!over) {
      return;
    }
    if (active?.id == over?.id) {
      return;
    }
    console.log('dragsuccess');
    const page_menu = tableDesigner?.pageMenu;
    if (page_menu) {
      if (active.data.current?.devData.type == 'table') {
        //找到菜单 交换 tableColumns
        const activeIndex = page_menu.data?.tableColumns.findIndex((i) => i.uid === active?.id);
        const overIndex = page_menu.data?.tableColumns.findIndex((i) => i.uid === over?.id);
        if (activeIndex > -1 && overIndex > -1) {
          page_menu.data.tableColumns = arrayMove(
            page_menu.data?.tableColumns,
            activeIndex,
            overIndex,
          );
          tableDesigner?.sort?.(page_menu.id, page_menu.data.tableColumns);
          //console.log('dragging over', activeIndex, overIndex, page_menu.data.tableColumns);
        }
      } else {
        //form
        //提交到后端服务器排序
        tableDesigner?.sortFormColumns?.(page_menu.id, [active?.id, over?.id]);
      }
    }
  };
};

export const DndContext = (props: Props) => {
  const [visible, setVisible] = useState(true);
  return (
    <DndKitContext
      collisionDetection={rectIntersection}
      accessibility={{ container: document.body }}
      {...props}
      onDragStart={(event) => {
        const { active } = event;
        const activeSchema = active?.data?.current?.schema;
        setVisible(!!activeSchema);
        if (props?.onDragStart) {
          props?.onDragStart?.(event);
        }
      }}
      onDragEnd={useDragEnd(props)}
    >
      <DragOverlay
        dropAnimation={{
          duration: 1,
          easing: 'cubic-bezier(0.18, 0.67, 0.6, 1.22)',
        }}
      >
        <span style={{ whiteSpace: 'nowrap' }}>拖拽中</span>
      </DragOverlay>
      {props.children}
    </DndKitContext>
  );
};
