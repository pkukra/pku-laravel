import React, { useState } from "react";
import {  Row, Col, Button } from "antd";

import SEP from "./SEP";


export default function Index({ nama_pasien, nomer_rm, kode_reg }) {
    return (
        <>
            <Row gutter={[8, 8]} style={{ width: 350 }}>
                <Col span={8}>
                    <SEP kode_reg={kode_reg} />
                </Col>

                <Col span={8}>
                    <Button block size="small">
                        Resume
                    </Button>
                </Col>

                <Col span={8}>
                    <Button block size="small">
                        Grouping
                    </Button>
                </Col>

                <Col span={8}>
                    <Button block size="small">
                        Klaim
                    </Button>
                </Col>
            </Row>
        </>
    );
}
