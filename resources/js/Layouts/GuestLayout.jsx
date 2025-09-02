import { Layout, Typography } from "antd";

export default function Guest({ children, showTitle = true }) {
    return (
        <Layout className="guest-layout">
            <Layout.Content>
                {showTitle && (
                    <Typography.Title level={2}>
                        Prima App PKU Muhammadiyah Karanganyar
                    </Typography.Title>
                )}
                <div className="guest-content">{children}</div>
            </Layout.Content>
        </Layout>
    );
}
