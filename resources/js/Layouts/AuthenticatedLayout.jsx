import React, { useState, useEffect } from "react";
import {
    AppstoreOutlined,
    BarChartOutlined,
    CloudOutlined,
    ShopOutlined,
    TeamOutlined,
    UploadOutlined,
    UserOutlined,
    VideoCameraOutlined,
} from "@ant-design/icons";
import { Breadcrumb, Layout, Menu, theme, Card } from "antd";
const { Header, Content, Footer, Sider } = Layout;
const { Meta } = Card;

function getItem(label, key, icon, children) {
    return {
        key,
        icon,
        children,
        label,
    };
}

const siderStyle = {
    overflow: "auto",
    height: "100vh",
    position: "sticky",
    insetInlineStart: 0,
    top: 0,
    bottom: 0,
    scrollbarWidth: "thin",
    scrollbarGutter: "stable",
};

const items = [
    UserOutlined,
    VideoCameraOutlined,
    UploadOutlined,
    BarChartOutlined,
    CloudOutlined,
    AppstoreOutlined,
    TeamOutlined,
    ShopOutlined,
].map((icon, index) => ({
    key: String(index + 1),
    icon: React.createElement(icon),
    label: `nav ${index + 1}`,
}));

const App = ({ children, user }) => {
    // Menyimpan status collapsed di state
    const [collapsed, setCollapsed] = useState(false);

    // Mengambil nilai collapsed dari localStorage saat pertama kali render
    useEffect(() => {
        const savedCollapsed = localStorage.getItem("collapsed");
        if (savedCollapsed !== null) {
            setCollapsed(JSON.parse(savedCollapsed)); // Menetapkan nilai yang disimpan
        }
    }, []);

    // Menyimpan status collapsed ke localStorage setiap kali terjadi perubahan
    const handleCollapseChange = (value) => {
        setCollapsed(value);
        localStorage.setItem("collapsed", JSON.stringify(value)); // Simpan status ke localStorage
    };

    const {
        token: { colorBgContainer, borderRadiusLG },
    } = theme.useToken();

    return (
        <Layout
            style={{
                minHeight: "100vh",
            }}
        >
            <Sider
                style={siderStyle}
                collapsible
                collapsed={collapsed}
                onCollapse={handleCollapseChange} // Menggunakan handleCollapseChange untuk perubahan
            >
                <div className="demo-logo-vertical" />
                <Menu
                    theme="dark"
                    mode="inline"
                    defaultSelectedKeys={["4"]}
                    items={items}
                />
            </Sider>
            <Layout>
                {/* <Header
                    style={{
                        padding: 0,
                        background: colorBgContainer,
                    }}
                /> */}
                <Content
                    style={{
                        margin: "0 16px",
                    }}
                >
                    <Breadcrumb
                        style={{
                            margin: "16px 0",
                        }}
                        items={[{ title: "halaman 1" }, { title: "halaman 2" }]}
                    />
                    {children}
                </Content>
                <Footer
                    style={{
                        textAlign: "center",
                    }}
                >
                    Ant Design ©{new Date().getFullYear()} Created by Ant UED
                </Footer>
            </Layout>
        </Layout>
    );
};

export default App;
