import { isObj } from '@/components/Sadmin/checkers';
import { getFromObject } from '@/components/Sadmin/helpers';
import { SaContext } from '@/components/Sadmin/posts/table';
import { Tag, Typography } from 'antd';
import { FC, useContext } from 'react';
const ItemTag: FC<{ color?: string; title?: string; bordered?: boolean }> = (props) => {
  const { color, title, bordered = true } = props;
  return (
    <Tag color={color} bordered={bordered}>
      <Typography.Text style={{ maxWidth: 80, color: 'inherit' }} ellipsis={{ tooltip: title }}>
        {title}
      </Typography.Text>
    </Tag>
  );
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
        if (!isObj(tag)) {
          const opt = options?.find((v) => v.id == tag);
          return opt ? (
            <ItemTag key={i} color={opt.color} title={opt.title} bordered={bordered} />
          ) : (
            <ItemTag key={i} color={color} title={tag} bordered={bordered} />
          );
        } else {
          //console.log('one tag', dataindex, searchData, tag);
          return <ItemTag key={i} color={tag.color} title={tag.title} bordered={bordered} />;
        }
      })}
    </>
  );
};

export default ItemTags;
