import React, { useState, useEffect } from "react";
import { Link, usePage } from "@inertiajs/react";
import {
    UserOutlined,
    CodeOutlined,
    HomeOutlined,
    MonitorOutlined,
    PoweroffOutlined,
} from "@ant-design/icons";
import { Layout, Menu } from "antd";

const { Sider, Content, Footer } = Layout;

const items = [
    {
        key: "", // Root path
        icon: <HomeOutlined />,
        label: <a href={route("dashboard")}>Home</a>,
    },
    {
        key: "rm-parent", // Untuk `/rm` dan turunannya
        icon: <CodeOutlined />,
        label: <a href={route("rm.index")}>RM</a>,
        children: [
            {
                key: "no-rm",
                label: <a href={route("rm.index")}>By No RM</a>,
            },
            {
                key: "rm/pasien-rujukan/list_rujukan",
                label: (
                    <a href={route("rm.pasien-rujukan.list_rujukan")}>
                        List Rajal
                    </a>
                ),
            },
            {
                key: "rm-ranap",
                label: (
                    <a href={route("rm.pasien-inap.list_inap")}>List Ranap</a>
                ),
            },
        ],
    },
    {
        key: "casemix", // Untuk `/casemix` dan turunannya
        icon: <MonitorOutlined />,
        label: (
            <a href={route("casemix.ranap-monit.list_pasien")}>Ranap Monitor</a>
        ),
    },
    {
        key: "profile",
        icon: <UserOutlined />,
        label: <a href={route("profile.edit")}>Profile</a>,
    },
    {
        key: "logout",
        icon: <PoweroffOutlined />,
        label: <a href={route("logout")}>Logout</a>,
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
                    {new Date().getFullYear()} Created By IT Team
                </Footer>
            </Layout>
        </Layout>
    );
};

export default App;
