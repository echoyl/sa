import { WxMenuModal } from '@/components/Sadmin/wxMenu';

export default () => {
  const content = [
    {
      uid: '5n2lrflbtnn',
      sub_button: [
        {
          uid: 'payw1wqjrhw',
          name: '\u65b0\u589e\u83dc\u5355x',
          type: 'view',
          url: '333',
        },
      ],
      name: '\u65b0\u589e\u83dc\u53551',
    },
    {
      uid: 'idzm0qai6zm',
      sub_button: [],
      name: '\u65b0\u589e\u83dc\u53552',
      type: 'click',
      key: '333',
    },
  ];
  return (
    <>
      <WxMenuModal value={content} />
    </>
  );
};
