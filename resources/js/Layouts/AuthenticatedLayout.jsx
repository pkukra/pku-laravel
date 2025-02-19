import React, { useState, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import { UserOutlined, CodeOutlined, HomeOutlined } from "@ant-design/icons";
import { Layout, Menu } from "antd";

const { Sider, Content, Footer } = Layout;

// Daftar menu dengan `key` sesuai dengan path utama
const items = [
    {
        key: "", // Root path
        icon: <HomeOutlined />,
        label: <Link href={route("dashboard")}>Home</Link>,
    },
    {
        key: "rm", // Untuk `/rm` dan turunannya
        icon: <CodeOutlined />,
        label: <Link href={route("rm.pasien-rujukan.index")}>RM Jalan</Link>,
    },
    {
        key: "profile", // Untuk `/profile`
        icon: <UserOutlined />,
        label: <Link href={route("profile.edit")}>Profile</Link>,
    },
];

const App = ({ children }) => {
    const [collapsed, setCollapsed] = useState(false);
    const { url } = usePage(); // Dapatkan URL dari Inertia.js

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

    // Ambil path utama (fragment pertama setelah domain)
    const currentKey = url.split("/")[1] || "";

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
                    selectedKeys={[currentKey]}
                    items={items}
                />
            </Sider>
            <Layout>
                <Content style={{ margin: "16px" }}>{children}</Content>
                <Footer style={{ textAlign: "center" }}>
                    X App PKU Muhammadiyah Karanganyar ©{" "}
                    {new Date().getFullYear()} Created By Dev Team
                </Footer>
            </Layout>
        </Layout>
    );
};

export default App;
