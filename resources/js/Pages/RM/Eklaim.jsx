import React, { useState, useEffect } from "react";
import { Head } from "@inertiajs/react";
import { Col, Row, Card, Tabs, Spin } from "antd";

import IndexTabIDRG from "./IDRG/IndexTabIDRG";
import IndexTabINACBG from "./INACBG/IndexTabINACBG";

import axios from "axios";

function EKlaim({ pasien, setDisableINACBG, disableINACBG }) {
    const menu = [
        {
            label: "IDRG",
            key: "1",
            children: (
                <IndexTabIDRG
                    pasien={pasien}
                    setDisableINACBG={setDisableINACBG}
                />
            ),
        },
        {
            label: "INACBG",
            key: "2",
            children: <IndexTabINACBG pasien={pasien} />,
            disabled: disableINACBG,
        },
    ];

    return (
        <>
            <Card>
                <Tabs
                    defaultActiveKey="1"
                    type="card"
                    size={"small"}
                    style={{ marginBottom: 32 }}
                    items={menu}
                />
            </Card>
        </>
    );
}

export default EKlaim;
