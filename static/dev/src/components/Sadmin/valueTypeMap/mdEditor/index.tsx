import UiwMDEditor from '@uiw/react-md-editor';
import { isNull } from 'lodash';
import { useState } from 'react';

export const MDEditorReal = (props) => {
  const { value: pvalue = '', onChange } = props;
  const [value, setValue] = useState(isNull(pvalue) ? '' : pvalue);
  return (
    <UiwMDEditor
      {...props}
      overflow={false}
      value={value}
      onChange={(v) => {
        setValue(v);
        onChange?.(v);
      }}
    />
  );
};

const MDEditor = (_, props) => {
  const { fieldProps } = props;
  return <MDEditorReal {...fieldProps} style={{ margin: '0 1px' }} />;
};
export default MDEditor;
