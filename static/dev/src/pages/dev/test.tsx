import { SaForm } from '@/components/Sadmin/posts/post';

export default () => {
  const tabs = [1, 2, 3, 4, 5, 6, 7];
  const tabs2 = tabs.map((v) => {
    return {
      tab: { title: 'tabname3333333333' + v },
      formColumns: [{ dataIndex: 'name' + v, title: 'name' + v }],
    };
  });
  return <SaForm tabs={tabs2} />;
};
