import GatewayForm from './create';

export default function GatewayEdit({ gateway, formats = [], configKeys = null, presets = [] }: any) {
  return <GatewayForm gateway={gateway} formats={formats} configKeys={configKeys} presets={presets} />;
}
