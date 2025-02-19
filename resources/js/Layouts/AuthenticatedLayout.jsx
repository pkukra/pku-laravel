import React, { useState, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import { UserOutlined, CodeOutlined, HomeOutlined } from "@ant-design/icons";
import { Layout, Menu } from "antd";

const { Sider, Content, Footer } = Layout;

// Daftar menu dengan `key` sebagai `href`
const items = [
    {
        key: "/",
        icon: <HomeOutlined />,
        label: <Link href="/">Home</Link>,
    },
    {
        key: "/rm/pasien-rujukan",
        icon: <CodeOutlined />,
        label: <Link href="/rm/pasien-rujukan">RM Jalan</Link>,
    },
    {
        key: "/profile",
        icon: <UserOutlined />,
        label: <Link href="/profile">Profile</Link>,
    },
];

const App = ({ children }) => {
    const [collapsed, setCollapsed] = useState(false);
    const { url } = usePage(); // Dapatkan URL saat ini dari Inertia.js

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
                collapsible
                collapsed={collapsed}
                onCollapse={handleCollapseChange}
            >
                <div className="demo-logo-vertical" />
                <Menu
                    theme="dark"
                    mode="inline"
                    selectedKeys={[url]}
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
