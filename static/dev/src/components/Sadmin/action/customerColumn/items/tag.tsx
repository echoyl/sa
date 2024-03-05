import { isObj } from '@/components/Sadmin/checkers';
import { getFromObject } from '@/components/Sadmin/helpers';
import { SaContext } from '@/components/Sadmin/posts/table';
import { Tag, Typography } from 'antd';
import { FC, useContext } from 'react';
const ItemTag: FC<{ color?: string; title?: string; bordered?: boolean }> = (props) => {
  const { color, title, bordered = true } = props;
  return title ? (
    <Tag color={color} bordered={bordered}>
      <Typography.Text style={{ maxWidth: 80, color: 'inherit' }} ellipsis={{ tooltip: title }}>
        {title}
      </Typography.Text>
    </Tag>
  ) : null;
};

const ItemTags: FC<{
  tags?: Array<{ color?: string; title?: string }>;
  color?: string;
  dataindex?: string | string[];
  bordered?: boolean;
}> = (props) => {
  const { tags = [], dataindex, color, bordered } = props;

  const { searchData } = useContext(SaContext);

  // console.log('dataindex', dataindex, searchData, tags);

  //读取配置参数 dataindex 或复数 s是否有
  const option = getFromObject(searchData, dataindex);
  const options = option ? option : getFromObject(searchData, dataindex + 's');
  return (
    <>
      {tags.map((tag, i) => {
        let xtag = tag;
        if (!isObj(tag)) {
          const opt = options?.find((v) => v.id == tag);
          xtag = opt ? opt : { color, title: tag };
        }
        return xtag.title ? <ItemTag key={i} {...xtag} bordered={bordered} /> : '-';
      })}
    </>
  );
};

export default ItemTags;
