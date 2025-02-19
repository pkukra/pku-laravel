import React, { useState, useEffect } from "react";
import { Link } from "@inertiajs/react"; // Gunakan Link dari Inertia.js
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
import { Layout, Menu } from "antd";

const { Sider, Content, Footer } = Layout;

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

// Buat daftar menu dengan navigasi menggunakan Inertia Link
const items = [
    { key: "1", icon: <UserOutlined />, label: <Link href="/">Home</Link> },
    {
        key: "2",
        icon: <VideoCameraOutlined />,
        label: <Link href="/about">About</Link>,
    },
    {
        key: "3",
        icon: <UploadOutlined />,
        label: <Link href="/contact">Contact</Link>,
    },
];

const App = ({ children }) => {
    const [collapsed, setCollapsed] = useState(false);

    useEffect(() => {
        const savedCollapsed = localStorage.getItem("collapsed");
        if (savedCollapsed !== null) {
            setCollapsed(JSON.parse(savedCollapsed));
        }
    }, []);

    const handleCollapseChange = (value) => {
        setCollapsed(value);
        localStorage.setItem("collapsed", JSON.stringify(value));
    };

    return (
        <Layout style={{ minHeight: "100vh" }}>
            <Sider
                style={siderStyle}
                collapsible
                collapsed={collapsed}
                onCollapse={handleCollapseChange}
            >
                <div className="demo-logo-vertical" />
                <Menu
                    theme="dark"
                    mode="inline"
                    defaultSelectedKeys={["1"]}
                    items={items}
                />
            </Sider>
            <Layout>
                <Content style={{ margin: "16px" }}>{children}</Content>
                <Footer style={{ textAlign: "center" }}>
                    SIMRS PKU Muhammadiyah Karanganyar ©{" "}
                    {new Date().getFullYear()} Created By Dev Team
                </Footer>
            </Layout>
        </Layout>
    );
};

export default App;
