import { CloseCircleOutlined } from '@ant-design/icons';
import { CheckCard } from '@ant-design/pro-components';
import { Typography } from 'antd';
import { useState } from 'react';

export default () => {
  const [show1, setShow1] = useState(false);
  const [show2, setShow2] = useState(false);
  const [show3, setShow3] = useState(false);
  return (
    <>
      <CheckCard
        checked={false}
        title={
          <Typography.Text style={{ width: 140 }} ellipsis>
            titletitletitletitletitletitletitletitletitle
          </Typography.Text>
        }
        avatar={undefined}
        description="description"
        extra={<CloseCircleOutlined onClick={() => {}} />}
        style={{ height: 98 }}
        size="small"
      />
    </>
  );
};
