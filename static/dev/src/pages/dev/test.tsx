import { getModelRelations } from '@/components/Sadmin/dev/table/baseFormColumns';
import Dnd from './test/dnd';

export default () => {
  const rls = getModelRelations(10026);
  return <Dnd />;
};
