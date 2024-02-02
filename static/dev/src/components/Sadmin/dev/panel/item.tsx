import { AreaMap, Bar, Column, Line, Pie } from '@ant-design/charts';
const ChartItem = (props) => {
  const { type, data, config } = props;
  //const { type } = chart;
  if (type == 'pie') {
    return (
      <Pie
        appendPadding={10}
        data={data}
        label={{
          type: 'inner',
          offset: '-30%',
          content: ({ percent }) => `${(percent * 100).toFixed(0)}%`,
          style: {
            fontSize: 14,
            textAlign: 'center',
          },
        }}
        interactions={[
          {
            type: 'element-active',
          },
        ]}
        radius={0.9}
        innerRadius={0.6}
        {...config}
      />
    );
  } else if (type == 'bar') {
    return <Bar data={data} {...config} />;
  } else if (type == 'column') {
    return <Column data={data} {...config} />;
  } else if (type == 'areaMap') {
    const configx = {
      map: {
        type: 'mapbox',
        style: 'blank',
        center: [120.19382669582967, 30.258134],
        zoom: 13,
        pitch: 0,
      },
      source: {
        data: data,
        parser: {
          type: 'geojson',
        },
      },
      autoFit: true,
      color: {
        field: config.field,
        value: ['#1A4397', '#3165D1', '#6296FE', '#98B7F7', '#DDE6F7', '#F2F5FC'].reverse(),
        scale: {
          type: 'quantile',
        },
      },
      style: {
        opacity: 1,
        stroke: '#eee',
        lineWidth: 0.8,
        lineOpacity: 1,
      },
      state: {
        active: true,
        select: {
          stroke: 'blue',
          lineWidth: 1.5,
          lineOpacity: 0.8,
        },
      },
      label: {
        visible: true,
        field: 'name',
        style: {
          fill: 'black',
          opacity: 0.5,
          fontSize: 12,
          spacing: 1,
          padding: [15, 15],
        },
      },
      // tooltip: {
      //   items: [
      //     {
      //       field: 'name',
      //       alias: '省份',
      //     },
      //     {
      //       field: 'unit_price',
      //       alias: '价格',
      //     },
      //   ],
      // },

      zoom: {
        position: 'bottomright',
      },
      legend: {
        position: 'bottomleft',
      },
      ...config,
    };
    return (
      <div style={{ width: '100%', height: '100%' }}>
        <AreaMap {...configx} />
      </div>
    );
  } else {
    return (
      <Line
        data={data}
        // title={{
        //   visible: true,
        //   text: chart.title,
        //   style: {
        //     fontSize: 14,
        //   },
        // }}
        // meta={{
        //   y: {
        //     alias: chart.y,
        //   },
        // }}
        // label={{
        //   content: (originData) => {
        //     return originData.gdp + '元';
        //   },
        //   position: 'top',
        //   // 'top', 'bottom', 'middle',
        //   // 配置样式
        //   style: {
        //     fill: '#000000',
        //     opacity: 0.6,
        //   },
        // }}
        {...config}
      />
    );
  }
};

export default ChartItem;
