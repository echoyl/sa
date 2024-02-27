import { SaForm } from '@/components/Sadmin/posts/post';

export default function App() {
  return (
    <>
      <SaForm
        tabs={[
          {
            title: 'test',
            formColumns: [
              {
                dataIndex: 'haha',
                title: '5555',
                valueType: 'group',
                name: 'hhhh',
                columns: [{ dataIndex: 'xxx', title: 'iner' }],
              },
            ],
          },
        ]}
        beforePost={(base) => {
          console.log(base);
          return false;
        }}
        msgcls={(data) => {
          console.log(data);
          return false;
        }}
      />
    </>
  );
}
